<?php

namespace CalculateDeposit;

use Connection\connection;
use Utility\Library;


class CalculateDep {
	private $con;
	private $conora;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttooracle();
		$this->conora = $connection->connecttooracle();
	}
	
	public function initDept($deptaccount_no,$amt_transfer,$itemtype,$fee_amt=0){
		$dataConst = $this->getConstantAcc($deptaccount_no);
		$penalty_amt = 0;
		if($dataConst["IS_CHECK_PENALTY"] == '1'){
			$penalty_amt = $this->calculatePenalty($dataConst,$amt_transfer,$itemtype,$deptaccount_no);
		}
		if($penalty_amt > 0){
			$arrayResult["PENALTY_AMT"] = $penalty_amt;
			$arrayResult['PENALTY_AMT_FORMAT'] = number_format($penalty_amt,2);
		}
		//$DataSeqAmt = $this->getSequestAmt($deptaccount_no);
		$sumAllTransfer = ($dataConst["PRNCBAL"]) - ($penalty_amt + $fee_amt + $amt_transfer);
		if($sumAllTransfer < $dataConst["MINPRNCBAL"]){
			$arrayResult['RESPONSE_CODE'] = "WS0100";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		$arrayResult["DEPTACCOUNT_NAME"] = $dataConst["DEPTACCOUNT_NAME"];
		$arrayResult['RESULT'] = TRUE;
		return $arrayResult;
	}
	
	public function getVcMapID($depttype_code,$sys_code='DEP'){
		$getvc = $this->conora->prepare("SELECT ACCOUNT_ID FROM VCMAPACCID WHERE SYSTEM_CODE = :sys_code 
										AND SLIPITEMTYPE_CODE = :sys_code AND SHRLONTYPE_CODE = :depttype_code");
		$getvc->execute([
			':depttype_code' => $depttype_code,
			':sys_code' => $sys_code
		]);
		$rowvc = $getvc->fetch(\PDO::FETCH_ASSOC);
		return $rowvc;
	}

	public function getWithdrawable($deptaccount_no){
		$DataAcc = $this->getConstantAcc($deptaccount_no);
		return $DataAcc["PRNCBAL"] - $DataAcc["MINPRNCBAL"] - $DataAcc["CHECKPEND_AMT"];
	}
	public function getSequestAmount($deptaccount,$itemtype_code,$amt_transfer=0){
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$config = json_decode($json,true);
		try {
			$clientWS = new \SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"as_deptaccount_no" => $deptaccount,
					"as_deptitemtype_code" => $itemtype_code,
					"adc_deptitem_amt" => $amt_transfer,
					"ai_status" => 0,
					"as_msglog" => null,
					"adc_amt" => 0.00
				];
				$resultWS = $clientWS->__call("of_check_sequest_online", array($argumentWS));
				file_put_contents('text.txt',json_encode($argumentWS));
				if($resultWS->ai_status == '0'){
					$arrayResult["CAN_DEPOSIT"] = TRUE;
					$arrayResult["CAN_WITHDRAW"] = TRUE;
				}else{
					$arrayResult["SEQUEST_DESC"] = $resultWS->as_msglog;
					$arrayResult["SEQUEST_AMOUNT"] = $resultWS->adc_amt;
					if($resultWS->ai_status == '5'){
						$arrayResult["CAN_DEPOSIT"] = FALSE;
						$arrayResult["CAN_WITHDRAW"] = TRUE;
					}else{
						if($resultWS->ai_status == '2' || $resultWS->ai_status == '3'){
							$arrayResult["CAN_DEPOSIT"] = FALSE;
							$arrayResult["CAN_WITHDRAW"] = FALSE;
						}else if($resultWS->ai_status == '2'){
							$arrayResult["CAN_DEPOSIT"] = TRUE;
							$arrayResult["CAN_WITHDRAW"] = FALSE;
						}else{
							$arrayResult["CAN_DEPOSIT"] = TRUE;
							$arrayResult["CAN_WITHDRAW"] = TRUE;
						}
					}
				}
				$arrayResult['RESULT'] = TRUE;
				return $arrayResult;
			}catch(\SoapFault $e){
				$arrayResult["RESPONSE_CODE"] = 'WS0104';
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}catch(\Throwable $e){
			$arrayResult["RESPONSE_CODE"] = 'WS0104';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function depositCheckDepositRights($deptaccount_no,$amt_transfer,$menu_component,$bank_code=null){
		$dataConst = $this->getConstantAcc($deptaccount_no);
		if($dataConst["MAXBALANCE_FLAG"] == '1' && $dataConst["MAXBALANCE"] > 0){
			if($dataConst["PRNCBAL"] + $amt_transfer > $dataConst["MAXBALANCE"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}
		if($dataConst["DEPTCLOSE_STATUS"] != '0'){
			$arrayResult['RESPONSE_CODE'] = "WS0089";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($dataConst["DEPTGROUP_CODE"] == '01'){
			$arrayResult['RESPONSE_CODE'] = "WS0090";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($amt_transfer < $dataConst["MINDEPT_AMT"]){
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['MINDEPT_AMT'] = $dataConst["MINDEPT_AMT"];
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		/*$getLimitAllDay = $this->conora->prepare("SELECT TOTAL_LIMIT,FIXED_UNIT FROM atmucftranslimit WHERE tran_desc = 'MCOOP' and tran_status = 1");
		$getLimitAllDay->execute();
		$rowLimitAllDay = $getLimitAllDay->fetch(\PDO::FETCH_ASSOC);
		if($rowLimitAllDay["FIXED_UNIT"] == '1'){
			$getSumAllDay = $this->conora->prepare("SELECT NVL(SUM(DPS.DEPTITEM_AMT),0) AS SUM_AMT 
												FROM DPDEPTMASTER DPM LEFT JOIN DPDEPTSTATEMENT DPS
												ON DPM.DEPTACCOUNT_NO = DPS.DEPTACCOUNT_NO and DPM.COOP_ID = DPS.COOP_ID
												WHERE DPM.MEMBER_NO = :member_no AND TO_CHAR(DPS.OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and DPS.ITEM_STATUS = '1' and DPS.entry_id IN('MCOOP','ICOOP')");
			$getSumAllDay->execute([':member_no' => $dataConst["MEMBER_NO"]]);
		}else{
			$getSumAllDay = $this->conora->prepare("SELECT NVL(SUM(DPS.DEPTITEM_AMT),0) AS SUM_AMT 
												FROM DPDEPTMASTER DPM LEFT JOIN DPDEPTSTATEMENT DPS
												ON DPM.DEPTACCOUNT_NO = DPS.DEPTACCOUNT_NO and DPM.COOP_ID = DPS.COOP_ID
												WHERE DPM.MEMBER_NO = :member_no AND TO_CHAR(DPS.OPERATE_DATE,'YYYY-MM') = TO_CHAR(SYSDATE,'YYYY-MM') 
												and DPS.ITEM_STATUS = '1' and DPS.entry_id IN('MCOOP','ICOOP')");
			$getSumAllDay->execute([':member_no' => $dataConst["MEMBER_NO"]]);
		}
		$rowSumAllDay = $getSumAllDay->fetch(\PDO::FETCH_ASSOC);
		$paymentAllDay = $rowSumAllDay["SUM_AMT"] + $amt_transfer;
		if($paymentAllDay > $rowLimitAllDay["TOTAL_LIMIT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0043';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}*/
		if($menu_component == 'TransferSelfDepInsideCoop' || $menu_component == 'TransferDepInsideCoop'){
			$menucheckrights = "and gca.allow_deposit_inside = '1'";
			$transfer_mode = "1";
		}else if($menu_component == 'TransactionDeposit' || $menu_component == 'TransactionWithdrawDeposit'){
			$menucheckrights = "and gca.allow_deposit_outside = '1'";
			$transfer_mode = "9";
		}else if($menu_component == 'TransferDepBuyShare'){
			$menucheckrights = "and gca.allow_buy_share = '1'";
			$transfer_mode = "3";
		}else if($menu_component == 'TransferDepPayLoan'){
			$menucheckrights = "and gca.allow_pay_loan = '1'";
			$transfer_mode = "2";
		}
		$checkUserAllow = $this->con->prepare("SELECT gua.is_use,gua.limit_transaction_amt FROM gcuserallowacctransaction gua 
												LEFT JOIN gcconstantaccountdept gca ON gua.id_accountconstant = gca.id_accountconstant
												WHERE gua.deptaccount_no = :deptaccount_no and gua.is_use = '1' ".$menucheckrights);
		$checkUserAllow->execute([':deptaccount_no' => $deptaccount_no]);
		$rowUserAllow = $checkUserAllow->fetch(\PDO::FETCH_ASSOC);
		if($rowUserAllow["IS_USE"] == "1"){
			if($amt_transfer > $rowUserAllow["LIMIT_TRANSACTION_AMT"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
			if(isset($bank_code)){
				$getConstantMapMenu = $this->con->prepare("SELECT TRIM(gbc.transaction_cycle) as transaction_cycle,gbc.max_numof_deposit,gbc.max_deposit,gbc.min_deposit,gbc.each_bank
														FROM gcbankconstantmapping gbm 
														LEFT JOIN gcbankconstant gbc 
														ON gbm.id_bankconstant = gbc.id_bankconstant
														WHERE gbm.bank_code = :bank_code and gbm.is_use = '1'");
				$getConstantMapMenu->execute([':bank_code' => $bank_code]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'time'){
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						
						if($rowConstMapMenu["MIN_DEPOSIT"] >= '0' && $amt_transfer < $rowConstMapMenu["MIN_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINDEPT_AMT'] = $rowConstMapMenu["MIN_DEPOSIT"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'day'){
						if($rowConstMapMenu["EACH_BANK"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																	and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																	and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_DEPOSIT"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'month'){
						if($rowConstMapMenu["EACH_BANK"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																	and to_char(TRUNC(operate_date),'YYYYMM') = to_char(TRUNC(SYSDATE),'YYYYMM')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																	and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_DEPOSIT"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'year'){
						if($rowConstMapMenu["EACH_BANK"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																	and to_char(TRUNC(operate_date),'YYYY') = to_char(TRUNC(SYSDATE),'YYYY')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																	and to_char(TRUNC(operate_date),'YYYY') = to_char(TRUNC(SYSDATE),'YYYY') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_DEPOSIT"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}else{
				$getConstantMapMenu = $this->con->prepare("SELECT TRIM(gbc.transaction_cycle) as transaction_cycle,gbc.max_numof_deposit,gbc.max_deposit,gbc.min_deposit
														FROM gcmenuconstantmapping gmm 
														LEFT JOIN gcbankconstant gbc 
														ON gmm.id_bankconstant = gbc.id_bankconstant
														WHERE gmm.menu_component = :menu_component and gmm.is_use = '1'");
				$getConstantMapMenu->execute([':menu_component' => $menu_component]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'time'){
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MIN_DEPOSIT"] >= '0' && $amt_transfer < $rowConstMapMenu["MIN_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINWITD_AMT'] = $rowConstMapMenu["MIN_DEPOSIT"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'day'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_DEPOSIT"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'month'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																and to_char(TRUNC(operate_date),'YYYYMM') = to_char(TRUNC(SYSDATE),'YYYYMM')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_DEPOSIT"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'year'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE destination = :deptaccount_no and trans_flag = '1'
																and to_char(TRUNC(operate_date),'YYYY') = to_char(TRUNC(SYSDATE),'YYYY')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_DEPOSIT"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_DEPOSIT"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_DEPOSIT"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function depositCheckWithdrawRights($deptaccount_no,$amt_transfer,$menu_component,$bank_code=null){
		$dataConst = $this->getConstantAcc($deptaccount_no);
		if($dataConst["DEPTCLOSE_STATUS"] != '0'){
			$arrayResult['RESPONSE_CODE'] = "WS0089";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($dataConst["DEPTGROUP_CODE"] == '01'){
			$arrayResult['RESPONSE_CODE'] = "WS0090";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($amt_transfer < $dataConst["MINWITD_AMT"]){
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['MINWITD_AMT'] = $dataConst["MINWITD_AMT"];
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		$getLimitAllDay = $this->conora->prepare("SELECT TOTAL_LIMIT,FIXED_UNIT FROM atmucftranslimit WHERE tran_desc = 'MCOOP' and tran_status = 1");
		$getLimitAllDay->execute();
		$rowLimitAllDay = $getLimitAllDay->fetch(\PDO::FETCH_ASSOC);
		if($rowLimitAllDay["FIXED_UNIT"] == '1'){
			$getSumAllDay = $this->conora->prepare("SELECT NVL(SUM(DPS.DEPTITEM_AMT),0) AS SUM_AMT 
												FROM DPDEPTMASTER DPM LEFT JOIN DPDEPTSTATEMENT DPS
												ON DPM.DEPTACCOUNT_NO = DPS.DEPTACCOUNT_NO and DPM.COOP_ID = DPS.COOP_ID
												WHERE DPM.MEMBER_NO = :member_no AND TO_CHAR(DPS.OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and SUBSTR(dps.DEPTITEMTYPE_CODE,0,1) = 'W' and DPS.ITEM_STATUS = '1' and DPS.entry_id IN('MCOOP','ICOOP')");
			$getSumAllDay->execute([':member_no' => $dataConst["MEMBER_NO"]]);
		}else{
			$getSumAllDay = $this->conora->prepare("SELECT NVL(SUM(DPS.DEPTITEM_AMT),0) AS SUM_AMT 
												FROM DPDEPTMASTER DPM LEFT JOIN DPDEPTSTATEMENT DPS
												ON DPM.DEPTACCOUNT_NO = DPS.DEPTACCOUNT_NO and DPM.COOP_ID = DPS.COOP_ID
												WHERE DPM.MEMBER_NO = :member_no AND TO_CHAR(DPS.OPERATE_DATE,'YYYY-MM') = TO_CHAR(SYSDATE,'YYYY-MM') 
												and SUBSTR(dps.DEPTITEMTYPE_CODE,0,1) = 'W' and DPS.ITEM_STATUS = '1' and DPS.entry_id IN('MCOOP','ICOOP')");
			$getSumAllDay->execute([':member_no' => $dataConst["MEMBER_NO"]]);
		}
		$rowSumAllDay = $getSumAllDay->fetch(\PDO::FETCH_ASSOC);
		$paymentAllDay = $rowSumAllDay["SUM_AMT"] + $amt_transfer;
		if($paymentAllDay > $rowLimitAllDay["TOTAL_LIMIT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0043';
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($menu_component == 'TransferSelfDepInsideCoop' || $menu_component == 'TransferDepInsideCoop'){
			$menucheckrights = "and gca.allow_withdraw_inside = '1'";
			$transfer_mode = "1";
		}else if($menu_component == 'TransactionDeposit' || $menu_component == 'TransactionWithdrawDeposit'){
			$menucheckrights = "and gca.allow_withdraw_outside = '1'";
			$transfer_mode = "9";
		}else if($menu_component == 'TransferDepBuyShare'){
			$menucheckrights = "and gca.allow_buy_share = '1'";
			$transfer_mode = "3";
		}else if($menu_component == 'TransferDepPayLoan'){
			$menucheckrights = "and gca.allow_pay_loan = '1'";
			$transfer_mode = "2";
		}
		$checkUserAllow = $this->con->prepare("SELECT gua.is_use,gua.limit_transaction_amt FROM gcuserallowacctransaction gua 
												LEFT JOIN gcconstantaccountdept gca ON gua.id_accountconstant = gca.id_accountconstant
												WHERE gua.deptaccount_no = :deptaccount_no and gua.is_use = '1' ".$menucheckrights);
		$checkUserAllow->execute([':deptaccount_no' => $deptaccount_no]);
		$rowUserAllow = $checkUserAllow->fetch(\PDO::FETCH_ASSOC);
		if($rowUserAllow["IS_USE"] == "1"){
			if($amt_transfer > $rowUserAllow["LIMIT_TRANSACTION_AMT"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
			if(isset($bank_code)){
				$getConstantMapMenu = $this->con->prepare("SELECT TRIM(gbc.transaction_cycle) as transaction_cycle,gbc.max_numof_withdraw,gbc.max_withdraw,gbc.min_withdraw,gbc.each_bank
														FROM gcbankconstantmapping gbm 
														LEFT JOIN gcbankconstant gbc 
														ON gbm.id_bankconstant = gbc.id_bankconstant
														WHERE gbm.bank_code = :bank_code and gbm.is_use = '1'");
				$getConstantMapMenu->execute([':bank_code' => $bank_code]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'time'){
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MIN_WITHDRAW"] >= '0' && $amt_transfer < $rowConstMapMenu["MIN_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINWITD_AMT'] = $rowConstMapMenu["MIN_WITHDRAW"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'day'){
						if($rowConstMapMenu["EACH_BANK"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_WITHDRAW"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'month'){
						if($rowConstMapMenu["EACH_BANK"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and to_char(TRUNC(operate_date),'YYYYMM') = to_char(TRUNC(SYSDATE),'YYYYMM')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and to_char(TRUNC(operate_date),'YYYYMM') = to_char(TRUNC(SYSDATE),'YYYYMM') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_WITHDRAW"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'year'){
						if($rowConstMapMenu["EACH_BANK"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and to_char(TRUNC(operate_date),'YYYY')  = to_char(TRUNC(SYSDATE),'YYYY')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and to_char(TRUNC(operate_date),'YYYY') = to_char(TRUNC(SYSDATE),'YYYY') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_WITHDRAW"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}else{
				$getConstantMapMenu = $this->con->prepare("SELECT TRIM(gbc.transaction_cycle) as transaction_cycle,gbc.max_numof_withdraw,gbc.max_withdraw,gbc.min_withdraw 
														FROM gcmenuconstantmapping gmm 
														LEFT JOIN gcbankconstant gbc 
														ON gmm.id_bankconstant = gbc.id_bankconstant
														WHERE gmm.menu_component = :menu_component and gmm.is_use = '1'");
				$getConstantMapMenu->execute([':menu_component' => $menu_component]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'time'){
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MIN_WITHDRAW"] >= '0' && $amt_transfer < $rowConstMapMenu["MIN_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINWITD_AMT'] = $rowConstMapMenu["MIN_WITHDRAW"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'day'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																and to_char(TRUNC(operate_date),'YYYYMMDD') = to_char(TRUNC(SYSDATE),'YYYYMMDD')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_WITHDRAW"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'month'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																and to_char(TRUNC(operate_date),'YYYYMM') = to_char(TRUNC(SYSDATE),'YYYYMM')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_WITHDRAW"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["TRANSACTION_CYCLE"] == 'year'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																and to_char(TRUNC(operate_date),'YYYY') = to_char(TRUNC(SYSDATE),'YYYY')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["MAX_NUMOF_WITHDRAW"] >= '0' && $rowTrans["NUMOF_TRANS"] > $rowConstMapMenu["MAX_NUMOF_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["MAX_WITHDRAW"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer > $rowConstMapMenu["MAX_WITHDRAW"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	private function getSequestAmt($deptaccount_no){
		$getSequestAmt = $this->conora->prepare("SELECT SEQUEST_BALANCE FROM dpdeptsequest WHERE deptaccount_no = :deptaccount_no 
												and sequest_status = '1' and item_status = '1'");
		$getSequestAmt->execute([':deptaccount_no' => $deptaccount_no]);
		$rowSeqAmt = $getSequestAmt->fetch(\PDO::FETCH_ASSOC);
		return $rowSeqAmt;
	}
	public function getConstantAcc($deptaccount_no){
		$getConst = $this->conora->prepare("SELECT dpm.DEPTCLOSE_STATUS,dpt.DEPTGROUP_CODE,dpm.DEPTTYPE_CODE,dpm.DEPTACCOUNT_NAME,dpm.PRNCBAL,dpt.MINPRNCBAL,
											dpt.MINWITD_AMT,dpt.MINDEPT_AMT,NVL(dpt.s_maxwitd_inmonth,0) as MAXWITHD_INMONTH,NVL(dpt.withcount_flag,0) as IS_CHECK_PENALTY,
											dpt.LIMITDEPT_FLAG,dpt.LIMITDEPT_AMT,dpt.MAXBALANCE,dpt.MAXBALANCE_FLAG,dpm.CHECKPEND_AMT,dpm.MEMBER_NO
											,NVL(dpt.s_period_inmonth,1) as PER_PERIOD_INCOUNT,NVL(dpt.withcount_unit,1) as PERIOD_UNIT_CHECK
											FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.DEPTTYPE_CODE  = dpt.DEPTTYPE_CODE
											WHERE dpm.DEPTACCOUNT_NO = :deptaccount_no");
		$getConst->execute([':deptaccount_no' => $deptaccount_no]);
		$rowConst = $getConst->fetch(\PDO::FETCH_ASSOC);
		return $rowConst;
	}
	private function calculatePenalty($dataConst,$amt_transfer,$itemtype,$deptaccount_no){
		$queryCheckPeriod = null;
		$penalty_amt = 0;
		if($dataConst["PER_PERIOD_INCOUNT"] > 0){
			if($dataConst["PERIOD_UNIT_CHECK"] == '1'){
				$monthCheck = date('Ym',strtotime('-'.($dataConst["PER_PERIOD_INCOUNT"]-1).' months'));
				$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN ".$monthCheck." and to_char(TRUNC(sysdate),'YYYYMM')";
			}else if($dataConst["PERIOD_UNIT_CHECK"] == '2'){
				$thisMonth = date('m');
				if($thisMonth >= 1 && $thisMonth <= 3){
					$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '01' and to_char(TRUNC(sysdate),'YYYY') || '03'";
				}else if($thisMonth >= 4 && $thisMonth <= 6){
					$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '04' and to_char(TRUNC(sysdate),'YYYY') || '06'";
				}else if($thisMonth >= 7 && $thisMonth <= 9){
					$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '07' and to_char(TRUNC(sysdate),'YYYY') || '09'";
				}else{
					$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '10' and to_char(TRUNC(sysdate),'YYYY') || '12'";
				}
			}else if($dataConst["PERIOD_UNIT_CHECK"] == '3'){
				$monthCheck = date('Y',strtotime('-'.($dataConst["PER_PERIOD_INCOUNT"]-1).' years'));
				$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYY') BETWEEN ".$monthCheck." and to_char(TRUNC(sysdate),'YYYY')";
			}else if($dataConst["PERIOD_UNIT_CHECK"] == '4'){
				$queryCheckPeriod = "";
			}else{
				$queryCheckPeriod = "";
			}
		}
		$checkItemIsCount = $this->conora->prepare("SELECT COUNT(*) as IS_NOTCOUNT FROM dpucfwithncount 
												WHERE depttype_code = :depttype_code and deptitem_code = :itemtype");
		$checkItemIsCount->execute([
			':depttype_code' => $dataConst["DEPTTYPE_CODE"],
			':itemtype' => $itemtype
		]);
		$rowItemCount = $checkItemIsCount->fetch(\PDO::FETCH_ASSOC);
		if($rowItemCount["IS_NOTCOUNT"] > 0){
			$getCountTrans = $this->conora->prepare("SELECT COUNT(dps.SEQ_NO) as C_TRANS FROM dpdeptstatement dps 
												WHERE dps.deptaccount_no = :deptaccount_no and SUBSTR(dps.DEPTITEMTYPE_CODE,0,1) = 'W' 
												and dps.deptitemtype_code <> :itemtype_code and dps.item_status = '1' ".$queryCheckPeriod);
			$getCountTrans->execute([
				':deptaccount_no' => $deptaccount_no,
				':itemtype_code' => $itemtype
			]);
		}else{
			$getCountTrans = $this->conora->prepare("SELECT COUNT(dps.SEQ_NO) as C_TRANS FROM dpdeptstatement dps 
												WHERE dps.deptaccount_no = :deptaccount_no and SUBSTR(dps.DEPTITEMTYPE_CODE,0,1) = 'W' 
												and dps.item_status = '1' ".$queryCheckPeriod);
			$getCountTrans->execute([
				':deptaccount_no' => $deptaccount_no
			]);
		}
		$rowCountTrans = $getCountTrans->fetch(\PDO::FETCH_ASSOC);
		$count_trans = $rowCountTrans["C_TRANS"] + 1;
		if($count_trans > $dataConst["MAXWITHD_INMONTH"]){
			$getContDeptTypeFee = $this->conora->prepare("SELECT CHARGE_FLAG,s_chrg_amt1 as MIN_FEE,s_chrg_perc1 as PERCENT_FEE,s_chrg_amt2 as MAX_FEE 
														FROM dpdepttype WHERE depttype_code = :depttype_code");
			$getContDeptTypeFee->execute([':depttype_code' => $dataConst["DEPTTYPE_CODE"]]);
			$rowContFee = $getContDeptTypeFee->fetch(\PDO::FETCH_ASSOC);
			if($rowContFee["CHARGE_FLAG"] == '1'){
				$penalty_amt = $rowContFee["PERCENT_FEE"] * $amt_transfer;
			}
			if($penalty_amt < $rowContFee["MIN_FEE"]){
				$penalty_amt = $rowContFee["MIN_FEE"];
			}
			if($penalty_amt > $rowContFee["MAX_FEE"]){
				$penalty_amt = $rowContFee["MAX_FEE"];
			}
		}
		return $penalty_amt;
	}
}
?>