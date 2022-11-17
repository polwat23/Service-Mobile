<?php

namespace CalculateLoan;

use Connection\connection;
use Utility\Library;


class CalculateLoan {
	private $con;
	private $conora;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttomysql();
		$this->conora = $connection->connecttooracle();
	}
	public function calculateIntAPI($loancontract_no,$amount=null){
		$dataCont = $this->getContstantLoanApproved($loancontract_no);
		$prin_bal = $amount * ($dataCont['LPD_SAL']/($dataCont['LPD_SAL']+$dataCont['LPD_INTE']));
		$prin_bal = round($prin_bal);
		$int_bal = $amount - $prin_bal;
		return [
			"AMOUNT_PAYMENT" => $amount,
			"PRIN_PAYMENT" => $prin_bal,
			"INT_PAYMENT" => $int_bal,
			"INT_PERIOD" => $int_bal,
			"INT_ARREAR" => 0,
			"INT_RETURN" => 0
		];
	}
	public function calculateIntArrAPI($loancontract_no,$amount=null){
		$dataCont = $this->getContstantLoanContract($loancontract_no);
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$url = $json_data["URL_CONSTANT"].'getconstantfunc/'.$json_data["COOP_KEY_PROD"].'/calculateintarrear';
		$header = ["requestId: ".$this->lib->randomText(10)];
		$dataInt = $this->dataChangeRateInt($dataCont["INT_CONTINTTABCODE"],$this->lib->convertdate($dataCont["LASTCALINT_DATE"],'y n d',false,true));
		$intRate = $this->getRateInt($dataCont["INT_CONTINTTABCODE"],date('Y-m-d'));
		$dataReq = array();
		$dataReq["condition"] = [$dataCont["LOANTYPE_CODE"]];
		if($dataCont["SPACE_KEEPING"] > 0){
			$dataReq["data"] = [
				"amount" => (float)($amount ?? $dataCont["PRINCIPAL_BALANCE"]),
				"loanBalance" => (float)$dataCont["PRINCIPAL_BALANCE"],
				"keepingAmount" => (float)$dataCont["SPACE_KEEPING"],
				"prinKeepingAmount" => (float)$dataCont["RKEEP_PRINCIPAL"],
				"calintFrom" => date('Y-m-d'),
				"calintTo" => date('Y-m-d',strtotime($dataCont["LASTPROCESS_DATE"])),
				"intArrear" => (float)$dataCont["INTEREST_ARREAR_SRC"],
				"intRate" => (float)$intRate["INTEREST_RATE"],
				"changeRateInt" => $dataInt["is_change"],
				"changeRateInfo" => $dataInt,
				"intReturn" => (float)$dataCont["INTEREST_RETURN"]
			];
		}else{
			$dataReq["data"] = [
				"amount" => (float)($amount ?? $dataCont["PRINCIPAL_BALANCE"]),
				"loanBalance" => (float)$dataCont["PRINCIPAL_BALANCE"],
				"keepingAmount" => (float)$dataCont["SPACE_KEEPING"],
				"calintFrom" => date('Y-m-d',strtotime($dataCont["LASTCALINT_DATE"])),
				"calintTo" => date('Y-m-d'),
				"intArrear" => (float)$dataCont["INTEREST_ARREAR_SRC"],
				"intRate" => (float)$intRate["INTEREST_RATE"],
				"changeRateInt" => $dataInt["is_change"],
				"changeRateInfo" => $dataInt,
				"intReturn" => (float)$dataCont["INTEREST_RETURN"]
				
			];
		}
		$interestResult = $this->lib->posting_data($url,$dataReq,$header);
		$arrResponse = json_decode($interestResult);
		if($arrResponse->RESULT){
			return [
				"INT_ARREAR" => $arrResponse->INT_ARREAR,
				"INT_PERIOD" => $arrResponse->INT_PERIOD
			];
		}else{
			return [
				"INT_ARREAR" => 0,
				"INT_PERIOD" => 0
			];
		}
	}

	public function calculateIntAccum($member_no){
		$getAccYear = $this->conora->prepare("SELECT ACCOUNT_YEAR FROM CMACCOUNTYEAR WHERE TRUNC(SYSDATE) BETWEEN TRUNC(ACCSTART_DATE) AND TRUNC(ACCEND_DATE)");
		$getAccYear->execute();
		$rowAccYear = $getAccYear->fetch(\PDO::FETCH_ASSOC);
		$getIntAccum = $this->conora->prepare("SELECT NVL(SUM(LNS.INTEREST_PAYMENT),0) AS INT_ACCUM FROM LNCONTMASTER LNM 
												LEFT JOIN LNCONTSTATEMENT LNS ON LNM.LOANCONTRACT_NO = LNS.LOANCONTRACT_NO,CMACCOUNTYEAR CMY
												WHERE LNM.MEMBER_NO = :member_no AND CMY.ACCOUNT_YEAR = :account_year AND TRUNC(ENTRY_DATE) >= TRUNC(ACCSTART_DATE) 
												AND TRUNC(ENTRY_DATE) <= TRUNC(ACCEND_DATE)");
		$getIntAccum->execute([
			':member_no' => $member_no,
			':account_year' => $rowAccYear["ACCOUNT_YEAR"]
		]);
		$rowIntAccum = $getIntAccum->fetch(\PDO::FETCH_ASSOC);
		return $rowIntAccum["INT_ACCUM"];
	}
	
	private function getRateInt($inttabcode,$date){
		$contLoan = $this->conora->prepare("SELECT INTEREST_RATE,TO_CHAR(EXPIRE_DATE,'YYYY-MM-DD') as EXPIRE_DATE
											,TO_CHAR(EFFECTIVE_DATE,'YYYY-MM-DD') as EFFECTIVE_DATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode
											and '".$date."' BETWEEN TO_CHAR(EFFECTIVE_DATE,'YYYY-MM-DD') and TO_CHAR(EXPIRE_DATE,'YYYY-MM-DD')");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		$constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC);
		return $constLoanRate;
	}
	private function dataChangeRateInt($inttabcode,$date){
		$changeRateData = array();
		$contLoan = $this->conora->prepare("SELECT TO_CHAR(EFFECTIVE_DATE,'YYYYMMDD') as EFFECTIVE_DATE,INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		while($constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC)){
			if($constLoanRate["EFFECTIVE_DATE"] > $date){
				if($constLoanRate["EFFECTIVE_DATE"] < date('Ymd')){
					if($constLoanRate["EFFECTIVE_DATE"] == (date('Y') + 1).'0101'){
						$changeRateData["is_change"] = FALSE;
					}else{
						$getDataNowInt = $this->conora->prepare("SELECT TO_CHAR(EFFECTIVE_DATE,'YYYY-MM-DD') as EFFECTIVE_DATE,INTEREST_RATE
																FROM lncfloanintratedet
																WHERE LOANINTRATE_CODE = :inttabcode and TO_CHAR(SYSDATE,'YYYYMMDD')
																BETWEEN TO_CHAR(EFFECTIVE_DATE,'YYYYMMDD') and TO_CHAR(EXPIRE_DATE,'YYYYMMDD')");
						$getDataNowInt->execute([':inttabcode' => $inttabcode]);
						$rowInt = $getDataNowInt->fetch(\PDO::FETCH_ASSOC);
						$getDataOldInt = $this->conora->prepare("SELECT TO_CHAR(EXPIRE_DATE,'YYYY-MM-DD') as EXPIRE_DATE,INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode and 
											".$date." BETWEEN TO_CHAR(EFFECTIVE_DATE,'YYYYMMDD') and TO_CHAR(EXPIRE_DATE,'YYYYMMDD')");
						$getDataOldInt->execute([':inttabcode' => $inttabcode]);
						$rowOldInt = $getDataOldInt->fetch(\PDO::FETCH_ASSOC);
						$changeRateData["exprieDate"] = $rowOldInt["EXPIRE_DATE"];
						$changeRateData["effectiveDate"] = $rowInt["EFFECTIVE_DATE"];
						$changeRateData["bfIntRate"] = (float)$rowOldInt["INTEREST_RATE"];
						$changeRateData["newIntRate"] = (float)$rowInt["INTEREST_RATE"];
						$changeRateData["is_change"] = TRUE;
					}
				}else{
					$changeRateData["is_change"] = FALSE;
				}
			}else{
				$changeRateData["is_change"] = FALSE;
			}
		}
		return $changeRateData;
	}

	private function checkChangeRateInt($inttabcode,$date){
		$change_rate = FALSE;
		$contLoan = $this->conora->prepare("SELECT TO_CHAR(EFFECTIVE_DATE,'YYYYMMDD') as EFFECTIVE_DATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode");
		$contLoan->execute([
			':inttabcode' => $inttabcode
		]);
		while($constLoanRate = $contLoan->fetch(\PDO::FETCH_ASSOC)){
			if($constLoanRate["EFFECTIVE_DATE"] > $date){
				if($constLoanRate["EFFECTIVE_DATE"] < date('Ymd')){
					if($constLoanRate["EFFECTIVE_DATE"] == (date('Y') + 1).'0101'){
						$change_rate = FALSE;
					}else{
						$change_rate = TRUE;
					}
				}else{
					$change_rate = FALSE;
				}
			}else{
				$change_rate = FALSE;
			}
		}
		return $change_rate;
	}
	public function getContstantLoanApproved($loancontract_no){
		$contLoan = $this->conora->prepare("select LPD_SAL, LPD_INTE from LOAN_M_PAYDEPT where lcont_id = :contract_no and lpd_num_inst = '0'");
		$contLoan->execute([':contract_no' => $loancontract_no]);
		$constLoanContract = $contLoan->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContract;
	}
	
	public function getContstantLoanContract($loancontract_no){
		$contLoan = $this->conora->prepare("SELECT ln.MEM_ID, ln.LREG_RECNO, ln.BR_NO, ln.CODE, ln.L_TYPE_CODE, ln.LSUB_CODE, ln.L_SUB2_CODE, ln.L_SUB_CODE_3, ln.LCONT_DATE,
											ln.LCONT_APPROVE_SAL,ln.LCONT_PAY_FLAG,ln.LCONT_MAX_INSTALL,ln.LCONT_SAL,ln.LCONT_ENDSAL,ln.LCONT_TYPE_INTEREST,ln.LCONT_INTEREST,
											ln.LCONT_SAL_DATE,ln.LCONT_DATE_OK,ln.LCONT_AMOUNT_INST,ln.LCONT_FLAG,ln.LCONT_AMOUNT_SAL,ln.LCONT_STATUS_FLAG,ln.LCONT_NUM_INST,
											ln.LNEXT_DATE,ln.LPAY_NUM,ln.DATE_END,LCONT_VERS,ln.LSAL_AMOUNT,ln.LCONT_PAYSAL,ln.FLAG_SAL,ln.LCONT_INTESAL,ln.USERNAME_CONT,ln.USERNAME_SAL,
											ln.LCONT_INT_COLLECT,ln.LCONT_TON_COLLECT,ln.LCONT_PAY_TYPE,ln.LCONT_PAY_BANK,ln.LCONT_PAY_CHEQUE,ln.LCONT_TRANS_BANK,ln.LCONT_TRANS_BRANCH,
											ln.LCONT_ACC_ID,ln.LCONT_STATUS_CONT,ln.LCONT_PAY_LAST_DATE,ln.LCONT_PAY_NO,ln.LCONT_NO_ENOUGH,ln.INST_COLL_YEAR,ln.PAY_DIV,ln.PAY_SECTION,ln.PAY_SUBSECTION,
											ln.PLACE_PAY,ln.MAINTAIN_BY_ID,ln.SAL_NO_ENOUGH,ln.PER_MONTH,ln.LCONT_PROFIT,ln.LINE,ln.PAGE,ln.FULLNAME_OLD,ln.BRANCH,ln.CARDLINE,ln.CARDPAGE,
											ln.SHEET_NO,ln.YEAR_SHEET,ln.PASSDUE_INST_PYEAR,ln.PASSDUE_SERV_PYEAR,ln.PASSDUE_AMTSAL_PYEAR,ln.PASSDUE_INST,ln.PASSDUE_SERV,ln.PASSDUE_AMTSAL,
											ln.PASSDUE_FINE,ln.PAY_MORE,ln.DISCOUNT_AMT,ln.AMOUNT_SAL_PYEAR,ln.PROFIT_PYEAR,ln.AMOUNT_SAL_ENDYEAR,ln.PROFIT_ENDYEAR,ln.ADD_SUMYEAR,ln.DEL_SUMYEAR,
											ln.TON_SUMYEAR, ln.PROFIT_SUMYEAR,ln.STATUS_NPL,ln.FOLLOWDEBT,ln.USER_ID,ln.CODE_BR,ln.ID_CARD,ln.STATUS_UPDATE,
											lt.L_TYPE_NAME AS LOAN_TYPE,lt.L_TYPE_CODE as LOAN_TYPE_CODE
											FROM LOAN_M_CONTACT ln LEFT JOIN LOAN_M_TYPE_NAME lt ON ln.L_TYPE_CODE = lt.L_TYPE_CODE 
											WHERE ln.LCONT_ID = :contract_no and ln.LCONT_STATUS_CONT IN('H','A','A1')");
		$contLoan->execute([':contract_no' => $loancontract_no]);
		$constLoanContract = $contLoan->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContract;
	}
	private function getRateIntTable($inttabcode){
		$conRate = $this->conora->prepare("SELECT INTEREST_RATE FROM lncfloanintratedet WHERE LOANINTRATE_CODE = :inttabcode
											and SYSDATE BETWEEN EFFECTIVE_DATE and EXPIRE_DATE");
		$conRate->execute([':inttabcode' => $inttabcode]);
		$rowRate = $conRate->fetch(\PDO::FETCH_ASSOC);
		return $rowRate["INTEREST_RATE"];
	}
	private function getLoanConstant(){
		$getLoanConstant = $this->conora->prepare("SELECT RDINTDEC_TYPE,RDINTSATANG_TYPE,DAYINYEAR FROM LNLOANCONSTANT");
		$getLoanConstant->execute();
		$constLoanContractCont = $getLoanConstant->fetch(\PDO::FETCH_ASSOC);
		return $constLoanContractCont;
	}
	public function repayLoan($conoracle,$contract_no,$amt_transfer,$penalty_amt,$config,$slipdocno,$operate_date,
	$log,$lib,$payload,$from_account_no,$lnslip_no,$member_no,$ref_no,$app_version){
		$dataCont = $this->getContstantLoanContract($contract_no);
		$interest = $this->calculateIntAPI($contract_no,$amt_transfer);
		$interestFull = $interest["INT_PAYMENT"];
		$prinPay = $interest["PRIN_PAYMENT"];
		$lastperiod = $dataCont["LAST_PERIODPAY"];
			$page_no =  $dataCont["LINE"] <= 24 ? $dataCont["PAGE"] : $dataCont["PAGE"] + 1;
			$line_no = $dataCont["LINE"] < 24 ? $dataCont["LINE"] + 1 : 1;
			if($dataCont["CARDPAGE"] % 2 == 0){
				$page_card = $dataCont["CARDLINE"] <= 23 ? $dataCont["CARDPAGE"] : $dataCont["CARDPAGE"] + 1;
				$line_card = $dataCont["CARDLINE"] < 23 ? $dataCont["CARDLINE"] + 1 : 1;
			}else{
				$page_card = $dataCont["CARDLINE"] <= 15 ? $dataCont["CARDPAGE"] : $dataCont["CARDPAGE"] + 1;
				$line_card = $dataCont["CARDLINE"] < 15 ? $dataCont["CARDLINE"] + 1 : 1;
			}
			$executeLnSTM = [
				':loancontract_no' => $contract_no,
				':lreg_recno' => $dataCont["LREG_RECNO"],
				':br_no' => $dataCont["BR_NO"],
				':code' => $dataCont["CODE"],
				':lpd_no' => $slipdocno,
				':lpd_num_inst' => $dataCont["LCONT_NUM_INST"]+1,
				':lpd_flag' => '10',
				':lpd_sal' => $prinPay,
				':lpd_inte' => $interestFull,
				':remark' => "รับชำระเงินยืม",
				':lpd_username' => "APP01",
				':flag_tpay' => "1",
				':sum_sal' => $amt_transfer,
				':lcont_bal_amount' => $dataCont["LCONT_AMOUNT_SAL"] - $amt_transfer,
				':line' => $line_no ,
				':page' => $page_no,
				':cardline' => $line_card,
				':cardpage' => $page_card,
				':lcont_bal_profit' => $dataCont["LCONT_PROFIT"] - $interestFull,
				':ref_no' => $ref_no
			];
			$insertSTMLoan = $conoracle->prepare("INSERT INTO loan_m_paydept (lcont_id,lreg_recno,br_no,code,lpd_no,lpd_num_inst,lpd_date,lpd_flag,lpd_sal,
												lpd_inte,remark,lpd_username,flag_tpay,sum_sal,flag_print,lcont_bal_amount,lcont_trancode,pay_in_day,line,
												page,cardline,cardpage,flag_cardprint,pay_more,lcont_bal_profit,followdebt , MAINTAIN_BY_ID,REF_NUM ) 
												VALUES (:loancontract_no,:lreg_recno,:br_no,:code,:lpd_no,:lpd_num_inst,TRUNC(SYSDATE),
												:lpd_flag,:lpd_sal,:lpd_inte,:remark,:lpd_username,:flag_tpay,:sum_sal,'N',:lcont_bal_amount,
												'10',0,:line,:page,:cardline,:cardpage,'N',0,:lcont_bal_profit,0,'0',:ref_no)");
			
			if($insertSTMLoan->execute($executeLnSTM)){
				// update loan contract
				$executeUpdateLoanContract = [
					":lcont_amount_inst" => $dataCont["LCONT_AMOUNT_INST"] - 1,
					":lcont_amount_sal" => $dataCont["LCONT_AMOUNT_SAL"] - $amt_transfer,
					":lcont_num_inst" => $dataCont["LCONT_NUM_INST"]+1,
					":lcont_profit" => $dataCont["LCONT_PROFIT"] - $interestFull,
					':line' => $line_no ,
					':page' => $page_no,
					':cardline' => $line_card,
					':cardpage' => $page_card,
					':lcont_id' => $contract_no,
					':br_no' => $dataCont["BR_NO"],
					':code' => $dataCont["CODE"]
				];
				$updateLoanContract = $conoracle->prepare("update loan_m_contact set lcont_amount_inst = :lcont_amount_inst,lcont_amount_sal = :lcont_amount_sal,lcont_num_inst = :lcont_num_inst,
												lcont_pay_last_date = TRUNC(SYSDATE),lcont_profit = :lcont_profit,
												line = :line,page = :page,cardline = :cardline,cardpage = :cardpage 
												where lcont_id = :lcont_id and br_no = :br_no and code = :code");
				if($updateLoanContract->execute($executeUpdateLoanContract)){
				file_put_contents(__DIR__.'Msgresponse.txt', json_encode($executeUpdateLoanContract,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
					// close dept
					if(($dataCont["LCONT_AMOUNT_SAL"] - $amt_transfer) <= 0){
						//update loan_t_guar
						$updateLoanGuar = $conoracle->prepare("update loan_t_guar set lg_be_avail = 0,mem_chk = 'Y'  where lcont_id = :lcont_id and br_no = :br_no and code = :code");
						if($updateLoanGuar->execute([
							':lcont_id' => $contract_no,
							':br_no' => $dataCont["BR_NO"],
							':code' => $dataCont["CODE"]
						])){
						}else{
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $operate_date,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $amt_transfer,
								':status_flag' => '0',
								':destination' => $contract_no,
								':response_code' => "WS0066",
								':response_message' => 'UPDATE loan_t_guar ไม่ได้'.$updateLoanGuar->queryString."\n".json_encode([
									':lcont_id' => $contract_no,
									':br_no' => $dataCont["BR_NO"],
									':code' => $dataCont["CODE"]
								])
							];
							$log->writeLog('repayloan',$arrayStruc);
							$arrayResult["RESPONSE_CODE"] = 'WS0066';
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						
						//update loan status
						$updateLoanStatus = $conoracle->prepare("update loan_m_contact set lcont_status_cont = 'S1' where lcont_id = :lcont_id and br_no = :br_no and code = :code");
						if($updateLoanStatus->execute([
							':lcont_id' => $contract_no,
							':br_no' => $dataCont["BR_NO"],
							':code' => $dataCont["CODE"]
						])){
						}else{
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $operate_date,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $amt_transfer,
								':status_flag' => '0',
								':destination' => $contract_no,
								':response_code' => "WS0066",
								':response_message' => 'UPDATE loan_m_contact ไม่ได้'.$updateLoanStatus->queryString."\n".json_encode([
									':lcont_id' => $contract_no,
									':br_no' => $dataCont["BR_NO"],
									':code' => $dataCont["CODE"]
								])
							];
							$log->writeLog('repayloan',$arrayStruc);
							$arrayResult["RESPONSE_CODE"] = 'WS0066';
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
					
					//update sys bill
					$updateSysBill = $conoracle->prepare("update sys_bill set bill_running = :bill_running where bill_id = '03'");
					if($updateSysBill->execute([
						":bill_running" => $lnslip_no
					])){
						$arrayResult['RESULT'] = TRUE;
						return $arrayResult;
					}else{
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $operate_date,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $amt_transfer,
							':status_flag' => '0',
							':destination' => $contract_no,
							':response_code' => "WS0066",
							':response_message' => 'UPDATE sys_bill ไม่ได้'.$updateSysBill->queryString."\n".json_encode([
								":bill_running" => $lnslip_no
							])
						];
						$log->writeLog('repayloan',$arrayStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0066';
						$arrayResult['RESULT'] = FALSE;
						return $arrayResult;
					}
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':status_flag' => '0',
						':destination' => $contract_no,
						':response_code' => "WS0066",
						':response_message' => 'UPDATE loan_m_contact ไม่ได้'.$updateLoanContract->queryString."\n".json_encode($executeUpdateLoanContract)
					];
					$log->writeLog('repayloan',$arrayStruc);
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $operate_date,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $amt_transfer,
					':status_flag' => '0',
					':destination' => $contract_no,
					':response_code' => "WS0066",
					':response_message' => 'INSERT loan_m_paydept ไม่ได้'.$insertSTMLoan->queryString."\n".json_encode($executeLnSTM)
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
	}
	public function paySlip($conoracle,$amt_transfer,$config,$slipdoc_no,$operate_date,
	$tofrom_accid,$slipwtd=null,$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$itemtypeWTD,$conmysql,$penalty_amt=0){
		$interest_accum = $this->calculateIntAccum($member_no);
		$getShareinfo = $conoracle->prepare("SELECT SHARESTK_AMT FROM SHSHAREMASTER WHERE member_no = :member_no");
		$getShareinfo->execute([':member_no' => $member_no]);
		$rowShare = $getShareinfo->fetch(\PDO::FETCH_ASSOC);
		$getMemberInfo = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMemberInfo->execute([':member_no' => $member_no]);
		$rowMember = $getMemberInfo->fetch(\PDO::FETCH_ASSOC);
		$arrExecuteSlSlip = [
			':coop_id' => $config["COOP_ID"],
			':payinslip_no' => $payinslip_no,
			':member_no' => $member_no,
			':document_no' => $slipdoc_no,
			':sliptype_code' => 'PX',
			':operate_date' => $operate_date,
			':sharevalue' => $rowShare["SHARESTK_AMT"] * 10,
			':intaccum_amt' => $interest_accum,
			':moneytype_code' => 'TRN',
			':tofrom_accid' => $tofrom_accid,
			':slipdep' => $slipwtd ?? null,
			':slip_amt' => $amt_transfer,
			':membgroup_code' => $rowMember["MEMBGROUP_CODE"]
		];
		$insertPayinSlip = $conoracle->prepare("INSERT INTO slslippayin(COOP_ID,PAYINSLIP_NO,MEMCOOP_ID,MEMBER_NO,DOCUMENT_NO,SLIPTYPE_CODE,
												SLIP_DATE,OPERATE_DATE,SHARESTKBF_VALUE,SHARESTK_VALUE,INTACCUM_AMT,MONEYTYPE_CODE,ACCID_FLAG,
												TOFROM_ACCID,REF_SYSTEM,REF_SLIPNO,SLIP_AMT,
												MEMBGROUP_CODE,ENTRY_ID,ENTRY_DATE)
												VALUES(:coop_id,:payinslip_no,:coop_id,:member_no,:document_no,:sliptype_code,
												TRUNC(TO_DATE(:operate_date,'yyyy/mm/dd  hh24:mi:ss')),
												TRUNC(TO_DATE(:operate_date,'yyyy/mm/dd  hh24:mi:ss')),
												:sharevalue,:sharevalue,:intaccum_amt,:moneytype_code,1,:tofrom_accid,'DEP',:slipdep,:slip_amt,:membgroup_code,
												'MOBILE',SYSDATE)");
		if($insertPayinSlip->execute($arrExecuteSlSlip)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $operate_date,
				':deptaccount_no' => $from_account_no,
				':amt_transfer' => $amt_transfer,
				':status_flag' => '0',
				':destination' => $payinslip_no,
				':response_code' => "WS0066",
				':response_message' => 'Insert slslippayin ไม่ได้'.$insertPayinSlip->queryString."\n".json_encode($arrExecuteSlSlip)
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipDet($conoracle,$amt_transfer,$config,$operate_date,
	$log,$payload,$from_account_no,$payinslip_no,$slipitemtype,$shrloantype_code,$itemtype_desc,$slipseq_no,$stmitemtype=null,$share_value=0,$ref_depslip=null){
		$executeSlDet = [
			':coop_id' => $config["COOP_ID"], 
			':payinslip_no' => $payinslip_no,
			':slipitemtype' => $slipitemtype,
			':slipseq_no' => $slipseq_no,
			':loantype_code' => $shrloantype_code,
			':itemtype_desc' => $itemtype_desc,
			':lastperiod' => 0,
			':itempay_amt' => $amt_transfer,
			':prin_bal' => $share_value + $amt_transfer,
			':stm_itemtype' => $stmitemtype ?? null,
			':bfperiod' => $dataShare["LAST_PERIOD"],
			':bfbal_share' => $share_value,
			':ref_docno' => $ref_depslip
		];
		$insertSLSlipDet = $conoracle->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
												SHRLONTYPE_CODE,CONCOOP_ID,SLIPITEM_DESC,PERIOD,ITEM_PAYAMT,ITEM_BALANCE,
												INTEREST_PERIOD,INTEREST_RETURN,STM_ITEMTYPE,
												BFPERIOD,BFSHRCONT_BALAMT,REF_DOCNO)
												VALUES(:coop_id,:payinslip_no,:slipitemtype,:slipseq_no,1,:loantype_code,:coop_id,:itemtype_desc,
												:lastperiod,:itempay_amt,:prin_bal,0,0,:stm_itemtype,:bfperiod,:bfbal_share,:ref_docno)");
		if($insertSLSlipDet->execute($executeSlDet)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $operate_date,
				':deptaccount_no' => $from_account_no,
				':amt_transfer' => $amt_transfer,
				':status_flag' => '0',
				':destination' => $member_no,
				':response_code' => "WS0065",
				':response_message' => 'INSERT slslippayindet ไม่ได้'.$insertSLSlipDet->queryString."\n".json_encode($executeSlDet)
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0065';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipLonDet($conoracle,$dataCont,$amt_transfer,$config,$operate_date,
	$log,$payload,$from_account_no,$payinslip_no,$slipitemtype,$shrloantype_code,$contract_no,$prinPay=0,$interest=0
	,$intarrear=0,$int_returnSrc=0,$interestPeriod=0,$slipseq_no=1,$ref_depslip=null){
		$lastperiod = $dataCont["LAST_PERIODPAY"] + 1;
		$executeSlDet = [
			':loancontract_no' => $contract_no ?? null,
			':payinslip_no' => $payinslip_no,
			':slipitemtype' => $slipitemtype,
			':slipseq_no' => $slipseq_no,
			':loantype_code' => $shrloantype_code,
			':itemtype_desc' => 'ชำระพิเศษ',
			':lastperiod' => $lastperiod,
			':prin_pay' => $prinPay,
			':int_pay' => $interest,
			':int_arrear' => 0,
			':itempay_amt' => $amt_transfer,
			':prin_bal' => $dataCont["PRINCIPAL_BALANCE"] - $prinPay,
			':principal' => $dataCont["PRINCIPAL_BALANCE"],
			':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
			':int_return' => $int_returnSrc,
			':stm_itemtype' => 'LPX',
			':bfperiod' => $dataCont["LAST_PERIODPAY"],
			':bfintarr' => $dataCont["INTEREST_ARREAR_SRC"],
			':lastprocess_date' => date('Y-m-d H:i:s',strtotime($dataCont["LASTPROCESS_DATE"])),
			':period_payment' => $dataCont["PERIOD_PAYMENT"],
			':payspec_method' => $dataCont["PAYSPEC_METHOD"],
			':rkeep_principal' => $dataCont["RKEEP_PRINCIPAL"],
			':rkeep_interest' => $dataCont["RKEEP_INTEREST"],
			':nkeep_interest' => $dataCont["NKEEP_INTEREST"],
			':int_period' => $interestPeriod,
			':ref_docno' => $ref_depslip
		];
		if($interestPeriod > 0){
			$insertSLSlipDet = $conoracle->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
													SHRLONTYPE_CODE,CONCOOP_ID,LOANCONTRACT_NO,SLIPITEM_DESC,PERIOD,PRINCIPAL_PAYAMT,INTEREST_PAYAMT,
													INTARREAR_PAYAMT,ITEM_PAYAMT,ITEM_BALANCE,PRNCALINT_AMT,CALINT_FROM,CALINT_TO,INTEREST_PERIOD,INTEREST_RETURN,STM_ITEMTYPE,
													BFPERIOD,BFINTARR_AMT,BFLASTCALINT_DATE,BFLASTPROC_DATE,BFPERIOD_PAYMENT,BFSHRCONT_BALAMT,BFCOUNTPAY_FLAG,
													BFPAYSPEC_METHOD,RKEEP_PRINCIPAL,RKEEP_INTEREST,NKEEP_INTEREST,BFINTRETURN_FLAG,REF_DOCNO)
													VALUES(:coop_id,:payinslip_no,:slipitemtype,:slipseq_no,1,:loantype_code,:coop_id,:loancontract_no,:itemtype_desc,
													:lastperiod,:prin_pay,:int_pay,:int_arrear,:itempay_amt,:prin_bal,:principal,
													TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),TRUNC(SYSDATE),:int_period,:int_return,
													:stm_itemtype,:bfperiod,
													:bfintarr,TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),
													TRUNC(TO_DATE(:lastprocess_date,'yyyy/mm/dd  hh24:mi:ss')),
													:period_payment,:principal,1,:payspec_method,:rkeep_principal,:rkeep_interest,:nkeep_interest,0,:ref_docno)");
		}else{
			$insertSLSlipDet = $conoracle->prepare("INSERT INTO slslippayindet(COOP_ID,PAYINSLIP_NO,SLIPITEMTYPE_CODE,SEQ_NO,OPERATE_FLAG,
													SHRLONTYPE_CODE,CONCOOP_ID,LOANCONTRACT_NO,SLIPITEM_DESC,PERIOD,PRINCIPAL_PAYAMT,INTEREST_PAYAMT,
													INTARREAR_PAYAMT,ITEM_PAYAMT,ITEM_BALANCE,PRNCALINT_AMT,CALINT_FROM,CALINT_TO,INTEREST_PERIOD,INTEREST_RETURN,STM_ITEMTYPE,
													BFPERIOD,BFINTARR_AMT,BFLASTCALINT_DATE,BFLASTPROC_DATE,BFPERIOD_PAYMENT,BFSHRCONT_BALAMT,BFCOUNTPAY_FLAG,
													BFPAYSPEC_METHOD,RKEEP_PRINCIPAL,RKEEP_INTEREST,NKEEP_INTEREST,BFINTRETURN_FLAG,REF_DOCNO)
													VALUES(:coop_id,:payinslip_no,:slipitemtype,:slipseq_no,1,:loantype_code,:coop_id,:loancontract_no,:itemtype_desc,
													:lastperiod,:prin_pay,:int_pay,:int_arrear,:itempay_amt,:prin_bal,:principal,
													TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss'))
													,:int_period,:int_return,:stm_itemtype,:bfperiod,
													:bfintarr,TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),
													TRUNC(TO_DATE(:lastprocess_date,'yyyy/mm/dd  hh24:mi:ss')),
													:period_payment,:principal,1,:payspec_method,:rkeep_principal,:rkeep_interest,:nkeep_interest,0,:ref_docno)");

		}
		if($insertSLSlipDet->execute($executeSlDet)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $operate_date,
				':deptaccount_no' => $from_account_no,
				':amt_transfer' => $amt_transfer,
				':status_flag' => '0',
				':destination' => $payinslip_no,
				':response_code' => "WS0066",
				':response_message' => 'INSERT slslippayindet ไม่ได้'.$insertSLSlipDet->queryString."\n".json_encode($executeSlDet)
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipLonOut($conoracle,$config,$payoutslip_no,$member_no,$sliptype_code,$document_no,$operate_date,$loantype_code,$loancontract_no,$amt_transfer,$payload,$deptaccount_no
	,$moneytype_code,$bank_code,$vcc_id,$log){
		$dataCont = $this->getContstantLoanContract($loancontract_no);
		$arrExecuteSlOutSlip = [
			$config["COOP_ID"],$payoutslip_no,$config["COOP_ID"],$member_no,
			$sliptype_code,$document_no ?? null,date('Y-m-d H:i:s',strtotime($operate_date)),date('Y-m-d H:i:s',strtotime($operate_date)),
			$loantype_code,$loancontract_no,$amt_transfer,$amt_transfer,
			$dataCont["LOANAPPROVE_AMT"],$dataCont["WITHDRAWABLE_AMT"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),$moneytype_code,$bank_code ?? null,$deptaccount_no,$vcc_id,$config["COOP_ID"]
		];
		$insertSLSlipPayout = $conoracle->prepare("INSERT INTO slslippayout(COOP_ID,PAYOUTSLIP_NO,MEMCOOP_ID,MEMBER_NO,SLIPTYPE_CODE,DOCUMENT_NO,SLIP_DATE,OPERATE_DATE,SHRLONTYPE_CODE,
													LOANCONTRACT_NO,PAYOUT_AMT,PAYOUTNET_AMT,BFLOANAPPROVE_AMT,BFWITHDRAW_AMT,CALINT_FROM,CALINT_TO,MONEYTYPE_CODE,EXPENSE_BANK,EXPENSE_BRANCH,EXPENSE_ACCID,
													TOFROM_ACCID,SLIP_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID)
													VALUES(?,?,?,?,?,?,TO_DATE(?,'yyyy/mm/dd  hh24:mi:ss'),TO_DATE(?,'yyyy/mm/dd  hh24:mi:ss'),
													?,?,?,?,?,?,TO_DATE(?,'yyyy/mm/dd  hh24:mi:ss'),SYSDATE,?,?,'ฮฮฮ',?,?,'1','MOBILE',SYSDATE,?)");
		if($insertSLSlipPayout->execute($arrExecuteSlOutSlip)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $deptaccount_no,
				':loancontract_no' => $loancontract_no,
				':request_amt' => $amt_transfer,
				':status_flag' => '0',
				':response_code' => "WS1040",
				':response_message' => 'Insert slslippayout ไม่ได้'.$insertSLSlipPayout->queryString."\n".json_encode($arrExecuteSlOutSlip)
			];
			$log->writeLog('receiveloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS1040';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function paySlipLonOutExpense($conoracle,$config,$payoutslip_no,$bank_account_no,$amt_transfer,$vccid,$payload,$operate_date,$loancontract_no,$log){
		$arrExecuteSlOutExpenseSlip = [
			$config["COOP_ID"],$payoutslip_no,$bank_account_no,$amt_transfer,$vccid
		];
		$insertSLSlipPayOutExpense = $conoracle->prepare("INSERT INTO slslippayoutexpense(COOP_ID,PAYOUTSLIP_NO,SEQ_NO,MONEYTYPE_CODE,EXPENSE_BANK,EXPENSE_BRANCH,EXPENSE_ACCID,EXPENSE_AMT,
														BANKFEE_AMT,TOFROM_ACCID)
														VALUES(?,?,'1','CBT','006','ฮฮฮ',?,?,?)");
		if($insertSLSlipPayOutExpense->execute($arrExecuteSlOutExpenseSlip)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $bank_account_no,
				':loancontract_no' => $loancontract_no,
				':request_amt' => $amt_transfer,
				':status_flag' => '0',
				':response_code' => "WS1040",
				':response_message' => 'Insert slslippayoutexpense ไม่ได้'.$insertSLSlipPayOutExpense->queryString."\n".json_encode($arrExecuteSlOutExpenseSlip)
			];
			$log->writeLog('receiveloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS1040';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function receiveLoanOD($conoracle,$config,$contract_no,$dataCont,$slipdocno,$amt_transfer,$lnslip_no,$ref_no,$destination,$fee_amt,$payload,$app_version,$operate_date,$log){
		$interest = $this->calculateIntArrAPI($contract_no,$amt_transfer);
		$interestFull = $interest["INT_ARREAR"];
		$prinPay = 0;
		$interestPeriod = $interest["INT_ARREAR"] - $dataCont["BFINTEREST_ARREAR"];
		if($interestPeriod < 0){
			$interestPeriod = 0;
		}
		$prinPay = $amt_transfer;
		$int_returnSrc = 0;
		$intArr = $interest["INT_ARREAR"];
		$lastperiod = $dataCont["LAST_PERIODPAY"];
		
		if($interestPeriod > 0){
			$executeLnSTM = [
				$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,
				'LRC',$slipdocno,$lastperiod,$prinPay,0,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,
				$dataCont["PRINCIPAL_BALANCE"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				$dataCont["BFINTEREST_ARREAR"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
				$lnslip_no,$dataCont["INTEREST_RETURN"]
			];
			$insertSTMLoan = $conoracle->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
													OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
													PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
													INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
													BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
													VALUES(?,?,?,?,TRUNC(SYSDATE),TRUNC(SYSDATE),
													TRUNC(SYSDATE),?,?,?,?,?,?,TO_DATE(?,'yyyy/mm/dd  hh24:mi:ss'),
													SYSDATE,?,?,?,
													?,?,1,'MOBILE',SYSDATE,?,?,?,SYSDATE,'1')");
		}else{
			if($dataCont["PRINCIPAL_BALANCE"] > 0){
				$executeLnSTM = [
					$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,
					'LRC',$slipdocno,$lastperiod,$prinPay,0,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,
					$dataCont["PRINCIPAL_BALANCE"],date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
					date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
					$dataCont["BFINTEREST_ARREAR"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
					$lnslip_no,$dataCont["INTEREST_RETURN"]
				];
				$insertSTMLoan = $conoracle->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
														OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
														PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
														INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
														BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
														VALUES(?,?,?,?,TRUNC(SYSDATE),TRUNC(SYSDATE),
														TRUNC(SYSDATE),?,?,?,?,?,?,TO_DATE(?,'yyyy/mm/dd  hh24:mi:ss'),
														TO_DATE(?,'yyyy/mm/dd  hh24:mi:ss'),?,?,?,
														?,?,1,'MOBILE',SYSDATE,?,?,?,SYSDATE,'1')");
			}else{
				$executeLnSTM = [
					$config["COOP_ID"],$contract_no,$dataCont["LAST_STM_NO"] + 1,
					'LRC',$slipdocno,$lastperiod,$prinPay,0,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,
					$dataCont["PRINCIPAL_BALANCE"],
					$dataCont["BFINTEREST_ARREAR"],$interestPeriod,$intArr,$int_returnSrc,'TRN',$config["COOP_ID"],
					$lnslip_no,$dataCont["INTEREST_RETURN"]
				];
				$insertSTMLoan = $conoracle->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
														OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
														PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
														INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
														BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
														VALUES(?,?,?,?,TRUNC(SYSDATE),TRUNC(SYSDATE),
														TRUNC(SYSDATE),?,?,?,?,?,?,TRUNC(SYSDATE),
														TRUNC(SYSDATE),?,?,?,
														?,?,1,'MOBILE',SYSDATE,?,?,?,SYSDATE,'1')");
			}
		}
		if($insertSTMLoan->execute($executeLnSTM)){
			$LoanDebt = $dataCont["PRINCIPAL_BALANCE"] + $prinPay;
			if((($LoanDebt / 12) % 10) == 0){
				$periodPayment = ($LoanDebt / 12);
			}else{
				$periodPayment = ($LoanDebt / 12) + (10 - (($LoanDebt / 12) % 10));
			}
			$executeLnMaster = [
				$dataCont["WITHDRAWABLE_AMT"] - $prinPay,$dataCont["PRINCIPAL_BALANCE"] + $prinPay,$lastperiod,
				$intArr,$dataCont["LAST_STM_NO"] + 1,floor($periodPayment),$contract_no
			];
			if(isset($dataCont["STARTCONT_DATE"]) && $dataCont["STARTCONT_DATE"] != ""){
				if($intArr > 0){
					$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET WITHDRAWABLE_AMT = ?,
																PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																INTEREST_ARREAR = ?,LAST_STM_NO = ?,PERIOD_PAYMENT = ?,LASTACCESS_DATE = TRUNC(SYSDATE)
																WHERE loancontract_no = ?");
				}else{
					$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET WITHDRAWABLE_AMT = ?,
																PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																INTEREST_ARREAR = ?,LAST_STM_NO = ?,PERIOD_PAYMENT = ?,LASTACCESS_DATE = TRUNC(SYSDATE)
																WHERE loancontract_no = ?");
				}
			}else{
				if($intArr > 0){
					$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET WITHDRAWABLE_AMT = ?,
																PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																STARTCONT_DATE = TRUNC(SYSDATE),
																LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																INTEREST_ARREAR = ?,LAST_STM_NO = ?,PERIOD_PAYMENT = ?,LASTACCESS_DATE = TRUNC(SYSDATE)
																WHERE loancontract_no = ?");
				}else{
					$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET WITHDRAWABLE_AMT = ?,
																PRINCIPAL_BALANCE = ?,LAST_PERIODPAY = ?,
																STARTCONT_DATE = TRUNC(SYSDATE),
																LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																INTEREST_ARREAR = ?,LAST_STM_NO = ?,PERIOD_PAYMENT = ?,LASTACCESS_DATE = TRUNC(SYSDATE)
																WHERE loancontract_no = ?");
				}
			}
			if($updateLnContmaster->execute($executeLnMaster)){
				if($intArr > 0){
					$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,fee_amt,penalty_amt,principal
															,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
															app_version,is_offset,bfkeeping,calint_to)
															VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:fee_amt,:penalty_amt,:principal,:interest,
															:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
															:app_version,:is_offset,:bfkeeping,NOW())");
					$insertTransLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $destination,
						':loancontract_no' => $contract_no,
						':amount' => $amt_transfer,
						':fee_amt' => $fee_amt,
						':penalty_amt' => 0,
						':principal' => $prinPay,
						':interest' => 0,
						':interest_return' => $int_returnSrc,
						':interest_arrear' => $intArr,
						':bfinterest_return' => $dataCont["INTEREST_RETURN"],
						':bfinterest_arrear' => $dataCont["BFINTEREST_ARREAR"],
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':app_version' => $app_version,
						':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
						':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"]
					]);
				}else{
					$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,fee_amt,penalty_amt,principal
															,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
															app_version,is_offset,bfkeeping,calint_to)
															VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:fee_amt,:penalty_amt,:principal,:interest,
															:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
															:app_version,:is_offset,:bfkeeping,:calint_from)");
					$insertTransLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $destination,
						':loancontract_no' => $contract_no,
						':amount' => $amt_transfer,
						':fee_amt' => $fee_amt,
						':penalty_amt' => $penalty_amt,
						':principal' => $prinPay,
						':interest' => 0,
						':interest_return' => $int_returnSrc,
						':interest_arrear' => $intArr,
						':bfinterest_return' => $dataCont["INTEREST_RETURN"],
						':bfinterest_arrear' => $dataCont["BFINTEREST_ARREAR"],
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':app_version' => $app_version,
						':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
						':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"],
						':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"]))
					]);
				}
				$arrayResult['RESULT'] = TRUE;
				return $arrayResult;
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':deptaccount_no' => $destination,
					':loancontract_no' => $contract_no,
					':request_amt' => $amt_transfer,
					':status_flag' => '0',
					':response_code' => "WS1040",
					':response_message' => 'UPDATE lncontmaster ไม่ได้'.$updateLnContmaster->queryString."\n".json_encode($executeLnMaster)
				];
				$log->writeLog('receiveloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS1040';
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $destination,
				':loancontract_no' => $contract_no,
				':request_amt' => $amt_transfer,
				':status_flag' => '0',
				':response_code' => "WS1040",
				':response_message' => "Insert lncontstatement ไม่ได้".json_encode($conoracle->errorInfo())
			];
			$log->writeLog('receiveloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS1040';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}

}
?>
