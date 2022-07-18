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
		//$this->con = $connection->connecttomysql();
		$dbhost = "127.0.0.1";
		$dbuser = "root";
		$dbpass = "EXAT2022";
		$dbname = "mobile_exat_test";

		$this->con = new \PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
		$this->con->exec("set names utf8mb4");


		$dbnameOra = "(DESCRIPTION =
					(ADDRESS_LIST =
					  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.1.201)(PORT = 1521))
					)
					(CONNECT_DATA =
					  (SERVICE_NAME = iorcl)
					)
				  )";
		$this->conora = new \PDO("oci:dbname=".$dbnameOra.";charset=utf8", "iscotest", "iscotest");
		$this->conora->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
		$this->conora->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
		//$this->conora = $connection->connecttooracle();
	}
	public function calculateIntAPI($loancontract_no,$amount=null){
		$dataCont = $this->getContstantLoanContract($loancontract_no);
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$url = $json_data["URL_CONSTANT"].'getconstantfunc/'.$json_data["COOP_KEY_PROD"].'/calculateintperiod';
		$header = ["requestId : ".$this->lib->randomText(10)];
		$dataInt = $this->dataChangeRateInt($dataCont["INT_CONTINTTABCODE"],$this->lib->convertdate($dataCont["LASTCALINT_DATE"],'y n d',false,true));
		$intRate = $this->getRateInt($dataCont["INT_CONTINTTABCODE"],date('Y-m-d'));
		$dataReq = array();
		$dataReq["condition"] = [$dataCont["LOANTYPE_CODE"]];
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
		$interestResult = $this->lib->posting_data($url,$dataReq,$header);
		$arrResponse = json_decode($interestResult);
		if($arrResponse->RESULT){
			return [
				"INT_PERIOD" => $arrResponse->INT_PAYMENT,
				"INT_RETURN" => $arrResponse->INT_RETURN
			];
		}else{
			return [
				"INT_PERIOD" => 0,
				"INT_RETURN" => 0
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
											WHERE LOANINTRATE_CODE = :inttabcode and TRUNC(SYSDATE) BETWEEN TRUNC(EFFECTIVE_DATE) and TRUNC(EXPIRE_DATE)");
						$getDataNowInt->execute([':inttabcode' => $inttabcode]);
						$rowInt = $getDataNowInt->fetch(\PDO::FETCH_ASSOC);
						$getDataOldInt = $this->conora->prepare("SELECT TO_CHAR(EXPIRE_DATE,'YYYY-MM-DD') as EXPIRE_DATE,INTEREST_RATE
											FROM lncfloanintratedet
											WHERE LOANINTRATE_CODE = :inttabcode and 
											TRUNC(".$date.") BETWEEN TO_CHAR(EFFECTIVE_DATE,'YYYYMMDD') and TO_CHAR(EXPIRE_DATE,'YYYYMMDD')");
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
	public function getContstantLoanContract($loancontract_no){
		$contLoan = $this->conora->prepare("SELECT LNM.LOANAPPROVE_AMT,LNM.PRINCIPAL_BALANCE,LNM.PERIOD_PAYMENT,LNM.PERIOD_PAYAMT,LNM.LAST_PERIODPAY,
											LNM.LOANTYPE_CODE,(LNM.INTEREST_ARREAR - (LNM.RKEEP_INTEREST - LNM.NKEEP_INTEREST)) as INTEREST_ARREAR,LNM.INTEREST_ARREAR as INTEREST_ARREAR_SRC
											,LNT.PXAFTERMTHKEEP_TYPE,LNM.RKEEP_PRINCIPAL,LNM.RKEEP_INTEREST,
											LNM.LASTCALINT_DATE,LNM.LOANPAYMENT_TYPE,LNT.CONTINT_TYPE,LNT.INTEREST_METHOD,LNT.PAYSPEC_METHOD,LNT.INTSTEP_TYPE,LNM.LASTPROCESS_DATE,
											(LNM.NKEEP_PRINCIPAL + LNM.NKEEP_INTEREST) as SPACE_KEEPING,LNM.INTEREST_RETURN,LNM.NKEEP_PRINCIPAL,LNM.NKEEP_INTEREST,
											(CASE WHEN LNM.LASTPROCESS_DATE < LNM.LASTCALINT_DATE OR LNM.LASTPROCESS_DATE IS NULL THEN '1' ELSE '0' END) AS CHECK_KEEPING,LNM.LAST_STM_NO,
											LNM.INT_CONTINTTYPE,LNM.INT_CONTINTRATE,LNM.INT_CONTINTTABCODE
											FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE
											WHERE lnm.loancontract_no = :contract_no and lnm.contract_status > 0 and lnm.contract_status <> 8");
		$contLoan->execute([':contract_no' => $loancontract_no]);
		$constLoanContract = $contLoan->fetch(\PDO::FETCH_ASSOC);
		$constLoanContract["INTEREST_RATE"] = $this->getRateIntTable($constLoanContract["INT_CONTINTTABCODE"]);
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
	$tofrom_accid,$slipwtd,$log,$lib,$payload,$from_account_no,$lnslip_no,$member_no,$ref_no,$app_version,$interest=0,$intReturn=0,$intFull=0){
		$dataCont = $this->getContstantLoanContract($contract_no);
		$interest = $interest;
		$intArr = 0;
		$prinPay = 0;
		if($amt_transfer <= $interest){
			$interest = $amt_transfer;
		}else{
			$prinPay = $amt_transfer - $interest;
		}
		if($prinPay < 0){
			$prinPay = 0;
		}
		$interestPeriod = $interest - $dataCont["INTEREST_ARREAR"];
		if(($intFull - $interestPeriod) > 0){
			$intArr = $intFull - $interestPeriod;
		}
		if($interestPeriod < 0){
			$intArr = $dataCont["INTEREST_ARREAR"] - $interest;
			$interestPeriod = 0;
		}
		$int_returnSrc = $intReturn;
		$lastperiod = $dataCont["LAST_PERIODPAY"];
		$interest_accum = $this->calculateIntAccum($member_no);
		$updateInterestAccum = $conoracle->prepare("UPDATE mbmembmaster SET ACCUM_INTEREST = :int_accum WHERE member_no = :member_no");
		if($updateInterestAccum->execute([
			':int_accum' => $interest_accum + $interest,
			':member_no' => $member_no
		])){
			$executeLnSTM = [
				':coop_id' => $config["COOP_ID"],
				':loancontract_no' => $contract_no,
				':lastseq_no' => $dataCont["LAST_STM_NO"] + 1,
				':stm_itemtype' => 'LPX',
				':document_no' => $slipdocno,
				':lastperiod' => $lastperiod,
				':prin_pay' => $prinPay,
				':prin_bal' => $dataCont["PRINCIPAL_BALANCE"] - $prinPay,
				':int_pay' => $interest,
				':principal' => $dataCont["PRINCIPAL_BALANCE"],
				':calint_from' => date('Y-m-d H:i:s',strtotime($dataCont["LASTCALINT_DATE"])),
				':bfintarr' => $dataCont["INTEREST_ARREAR_SRC"],
				':int_arr' => $intArr,
				':int_return' => $int_returnSrc,
				':moneytype_code' => 'TRN',
				':ref_slipno' => $lnslip_no,
				':bfint_return' => $dataCont["INTEREST_RETURN"],
				':int_period' => $interestPeriod
			];
			if($interestPeriod > 0){
				$insertSTMLoan = $conoracle->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
														OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
														PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
														INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
														BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
														VALUES(:coop_id,:loancontract_no,:lastseq_no,:stm_itemtype,TRUNC(SYSDATE),TRUNC(SYSDATE),
														TRUNC(SYSDATE),:document_no,:lastperiod,:prin_pay,:int_pay,:prin_bal,:principal,
														TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),
														TRUNC(SYSDATE),:bfintarr,:int_period,:int_arr,
														:int_return,:moneytype_code,1,'MOBILE',SYSDATE,:coop_id,:ref_slipno,:bfint_return,TRUNC(SYSDATE),'1')");
			}else{
				$insertSTMLoan = $conoracle->prepare("INSERT INTO lncontstatement(COOP_ID,LOANCONTRACT_NO,SEQ_NO,LOANITEMTYPE_CODE,SLIP_DATE,
														OPERATE_DATE,ACCOUNT_DATE,REF_DOCNO,PERIOD,PRINCIPAL_PAYMENT,INTEREST_PAYMENT,PRINCIPAL_BALANCE,
														PRNCALINT_AMT,CALINT_FROM,CALINT_TO,BFINTARREAR_AMT,INTEREST_PERIOD,INTEREST_ARREAR,
														INTEREST_RETURN,MONEYTYPE_CODE,ITEM_STATUS,ENTRY_ID,ENTRY_DATE,ENTRY_BYCOOPID,REF_SLIPNO,
														BFINTRETURN_AMT,INTACCUM_DATE,SYNC_NOTIFY_FLAG)
														VALUES(:coop_id,:loancontract_no,:lastseq_no,:stm_itemtype,TRUNC(SYSDATE),TRUNC(SYSDATE),
														TRUNC(SYSDATE),:document_no,:lastperiod,:prin_pay,:int_pay,:prin_bal,:principal,
														TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss')),TRUNC(TO_DATE(:calint_from,'yyyy/mm/dd  hh24:mi:ss'))
														,:bfintarr,:int_period,:int_arr,
														:int_return,:moneytype_code,1,'MOBILE',SYSDATE,:coop_id,:ref_slipno,:bfint_return,TRUNC(SYSDATE),'1')");
			}
			if($insertSTMLoan->execute($executeLnSTM)){
				$executeLnMaster = [
					':prin_bal' => $dataCont["PRINCIPAL_BALANCE"] - $prinPay,
					':loancontract_no' => $contract_no,
					':lastperiod_pay' => $lastperiod,
					':int_arr' => $intArr,
					':int_accum' => $interest_accum + $interest,
					':prinpay' => $prinPay,
					':int_return' => $int_returnSrc,
					':int_pay' => $interest,
					':laststmno' => $dataCont["LAST_STM_NO"] + 1,
				];
				if($interestPeriod > 0){
					if($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0){
						if($dataCont["LOANTYPE_CODE"] == '23'){
							$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																		LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																		INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																		INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno
																		WHERE loancontract_no = :loancontract_no");

						}else{
							$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																		LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																		INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																		INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno,
																		CONTRACT_STATUS = '-1'
																		WHERE loancontract_no = :loancontract_no");
						}
					}else{
						$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																	PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																	LASTPAYMENT_DATE = TRUNC(SYSDATE),LASTCALINT_DATE = TRUNC(SYSDATE),
																	INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																	INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																	INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno
																	WHERE loancontract_no = :loancontract_no");
					}
				}else{
					if($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0){
						if($dataCont["LOANTYPE_CODE"] == '23'){
							$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																		LASTPAYMENT_DATE = TRUNC(SYSDATE),
																		INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																		INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno
																		WHERE loancontract_no = :loancontract_no");

						}else{
							$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																		PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																		LASTPAYMENT_DATE = TRUNC(SYSDATE),
																		INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																		INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																		INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno,
																		CONTRACT_STATUS = '-1'
																		WHERE loancontract_no = :loancontract_no");
						}
					}else{
						$updateLnContmaster = $conoracle->prepare("UPDATE lncontmaster SET 
																	PRINCIPAL_BALANCE = :prin_bal,LAST_PERIODPAY = :lastperiod_pay,
																	LASTPAYMENT_DATE = TRUNC(SYSDATE),
																	INTEREST_ARREAR = :int_arr,INTEREST_ACCUM = :int_accum,
																	INTEREST_RETURN = :int_return,PRNPAYMENT_AMT = PRNPAYMENT_AMT + :prinpay,
																	INTPAYMENT_AMT = INTPAYMENT_AMT + :int_pay,LAST_STM_NO = :laststmno
																	WHERE loancontract_no = :loancontract_no");
					}
				}
				if($updateLnContmaster->execute($executeLnMaster)){
					if($interestPeriod > 0){
						$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,penalty_amt,principal
																,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
																app_version,is_offset,bfkeeping,calint_to)
																VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:penalty_amt,:principal,:interest,
																:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
																:app_version,:is_offset,:bfkeeping,NOW())");
						$insertTransLog->execute([
							':ref_no' => $ref_no,
							':from_account' => $from_account_no,
							':loancontract_no' => $contract_no,
							':amount' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':principal' => $prinPay,
							':interest' => $interest,
							':interest_return' => $int_returnSrc,
							':interest_arrear' => $intArr,
							':bfinterest_return' => $dataCont["INTEREST_RETURN"],
							':bfinterest_arrear' => $dataCont["INTEREST_ARREAR"],
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':app_version' => $app_version,
							':is_offset' => ($dataCont["RKEEP_PRINCIPAL"] == 0 && $dataCont["PRINCIPAL_BALANCE"] - $prinPay == 0) ? '2' : '1',
							':bfkeeping' => $dataCont["RKEEP_PRINCIPAL"]
						]);
					}else{
						$insertTransLog = $this->con->prepare("INSERT INTO gcrepayloan(ref_no,from_account,loancontract_no,source_type,amount,penalty_amt,principal
																,interest,interest_return,interest_arrear,bfinterest_return,bfinterest_arrear,member_no,id_userlogin,
																app_version,is_offset,bfkeeping,calint_to)
																VALUES(:ref_no,:from_account,:loancontract_no,'1',:amount,:penalty_amt,:principal,:interest,
																:interest_return,:interest_arrear,:bfinterest_return,:bfinterest_arrear,:member_no,:id_userlogin,
																:app_version,:is_offset,:bfkeeping,:calint_from)");
						$insertTransLog->execute([
							':ref_no' => $ref_no,
							':from_account' => $from_account_no,
							':loancontract_no' => $contract_no,
							':amount' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':principal' => $prinPay,
							':interest' => $interest,
							':interest_return' => $int_returnSrc,
							':interest_arrear' => $intArr,
							':bfinterest_return' => $dataCont["INTEREST_RETURN"],
							':bfinterest_arrear' => $dataCont["INTEREST_ARREAR"],
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
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':status_flag' => '0',
						':destination' => $contract_no,
						':response_code' => "WS0066",
						':response_message' => 'UPDATE lncontmaster ไม่ได้'.$updateLnContmaster->queryString."\n".json_encode($executeLnMaster)
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
					':response_message' => 'INSERT lncontstatement ไม่ได้'.$insertSTMLoan->queryString."\n".json_encode($executeLnSTM)
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
				':response_message' => 'UPDATE mbmembmaster ไม่ได้'.$updateInterestAccum->queryString."\n".json_encode([
					':int_accum' => $interest_accum + $interest,
					':member_no' => $member_no
				])
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
			':coop_id' => $config["COOP_ID"], 
			':payinslip_no' => $payinslip_no,
			':slipitemtype' => $slipitemtype,
			':slipseq_no' => $slipseq_no,
			':loantype_code' => $shrloantype_code,
			':loancontract_no' => $contract_no ?? null,
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
				':response_message' => 'INSERT slslippayindet ไม่ได้'.$insertSLSlipDet->queryString."\n".json_encode($conoracle->errorInfo())
			];
			$log->writeLog('repayloan',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
}
?>
