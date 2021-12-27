<?php

namespace CalculateDeposit;

use Connection\connection;
use Utility\Library;


class CalculateDep {
	private $con;
	private $conms;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttomysql();
		$this->conms = $connection->connecttosqlserver();
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
		$DataSeqAmt = $this->getSequestAmt($deptaccount_no);
		if(substr($itemtype,0,1) == 'W'){
			if($DataSeqAmt["CAN_WITHDRAW"]){
				$sumAllTransfer = ($dataConst["PRNCBAL"] - $DataSeqAmt["SEQUEST_AMOUNT"]) - ($penalty_amt + $fee_amt + $amt_transfer);
				if($sumAllTransfer < $dataConst["MINPRNCBAL"]){
					$arrayResult['RESPONSE_CODE'] = "WS0100";
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
				$arrayResult["DEPTACCOUNT_NAME"] = $dataConst["DEPTACCOUNT_NAME"];
				$arrayResult['RESULT'] = TRUE;
				return $arrayResult;
			}else{
				$arrayResult['IS_SEQUEST'] = TRUE;
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			if($DataSeqAmt["CAN_DEPOSIT"]){
				$sumAllTransfer = ($dataConst["PRNCBAL"] - $DataSeqAmt["SEQUEST_AMOUNT"]) - ($penalty_amt + $fee_amt + $amt_transfer);
				if($sumAllTransfer < $dataConst["MINPRNCBAL"]){
					$arrayResult['RESPONSE_CODE'] = "WS0100";
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
				$arrayResult["DEPTACCOUNT_NAME"] = $dataConst["DEPTACCOUNT_NAME"];
				$arrayResult['RESULT'] = TRUE;
				return $arrayResult;
			}else{
				$arrayResult['IS_SEQUEST'] = TRUE;
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}
		
	}
	public function getWithdrawable($deptaccount_no){
		$DataAcc = $this->getConstantAcc($deptaccount_no);
		return $DataAcc["PRNCBAL"] - $DataAcc["MINPRNCBAL"];
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
		/*$checkUserAllow = $this->con->prepare("SELECT gua.is_use,gua.limit_transaction_amt FROM gcuserallowacctransaction gua 
												LEFT JOIN gcconstantaccountdept gca ON gua.id_accountconstant = gca.id_accountconstant
												WHERE gua.deptaccount_no = :deptaccount_no and gua.is_use = '1' ".$menucheckrights);
		$checkUserAllow->execute([':deptaccount_no' => $deptaccount_no]);
		$rowUserAllow = $checkUserAllow->fetch(\PDO::FETCH_ASSOC);
		if($rowUserAllow["is_use"] == "1"){*/
			/*if($amt_transfer > $rowUserAllow["limit_transaction_amt"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}*/
			if(isset($bank_code)){
				$getConstantMapMenu = $this->con->prepare("SELECT gbc.transaction_cycle,gbc.max_numof_deposit,gbc.max_deposit,gbc.min_deposit,gbc.each_bank
														FROM gcbankconstantmapping gbm 
														LEFT JOIN gcbankconstant gbc 
														ON gbm.id_bankconstant = gbc.id_bankconstant
														WHERE gbm.bank_code = :bank_code and gbm.is_use = '1'");
				$getConstantMapMenu->execute([':bank_code' => $bank_code]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["transaction_cycle"] == 'time'){
						if($rowConstMapMenu["max_deposit"] >= '0' && $amt_transfer > $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["min_deposit"] >= '0' && $amt_transfer < $rowConstMapMenu["min_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINDEPT_AMT'] = $rowConstMapMenu["min_deposit"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'day'){
						if($rowConstMapMenu["each_bank"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																	and DATE_FORMAT(operate_date,'%Y%M%D') = DATE_FORMAT(NOW(),'%Y%M%D')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																	and DATE_FORMAT(operate_date,'%Y%M%D') = DATE_FORMAT(NOW(),'%Y%M%D') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_deposit"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_deposit"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'month'){
						if($rowConstMapMenu["each_bank"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																	and DATE_FORMAT(operate_date,'%Y%M') = DATE_FORMAT(NOW(),'%Y%M')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																	and DATE_FORMAT(operate_date,'%Y%M') = DATE_FORMAT(NOW(),'%Y%M') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_deposit"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_deposit"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'year'){
						if($rowConstMapMenu["each_bank"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																	and DATE_FORMAT(operate_date,'%Y') = DATE_FORMAT(NOW(),'%Y')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																	and DATE_FORMAT(operate_date,'%Y') = DATE_FORMAT(NOW(),'%Y') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_deposit"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_deposit"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}else{
				$getConstantMapMenu = $this->con->prepare("SELECT gbc.transaction_cycle,gbc.max_numof_deposit,gbc.max_deposit,gbc.min_deposit
														FROM gcmenuconstantmapping gmm 
														LEFT JOIN gcbankconstant gbc 
														ON gmm.id_bankconstant = gbc.id_bankconstant
														WHERE gmm.menu_component = :menu_component and gmm.is_use = '1'");
				$getConstantMapMenu->execute([':menu_component' => $menu_component]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["transaction_cycle"] == 'time'){
						if($rowConstMapMenu["max_deposit"] >= '0' && $amt_transfer > $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["min_deposit"] >= '0' && $amt_transfer < $rowConstMapMenu["min_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINWITD_AMT'] = $rowConstMapMenu["min_deposit"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'day'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																and DATE_FORMAT(operate_date,'%Y%M%D') = DATE_FORMAT(NOW(),'%Y%M%D')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_deposit"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_deposit"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'month'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																and DATE_FORMAT(operate_date,'%Y%M') = DATE_FORMAT(NOW(),'%Y%M')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_deposit"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_deposit"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'year'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '1'
																and DATE_FORMAT(operate_date,'%Y') = DATE_FORMAT(NOW(),'%Y')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_deposit"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_deposit"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		/*}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}*/
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
		if($rowUserAllow["is_use"] == "1"){
			if($amt_transfer > $rowUserAllow["limit_transaction_amt"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
			if(isset($bank_code)){
				$getConstantMapMenu = $this->con->prepare("SELECT gbc.transaction_cycle,gbc.max_numof_withdraw,gbc.max_withdraw,gbc.min_withdraw,gbc.each_bank
														FROM gcbankconstantmapping gbm 
														LEFT JOIN gcbankconstant gbc 
														ON gbm.id_bankconstant = gbc.id_bankconstant
														WHERE gbm.bank_code = :bank_code and gbm.is_use = '1'");
				$getConstantMapMenu->execute([':bank_code' => $bank_code]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["transaction_cycle"] == 'time'){
						if($rowConstMapMenu["max_withdraw"] >= '0' && $amt_transfer > $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["min_withdraw"] >= '0' && $amt_transfer < $rowConstMapMenu["min_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINWITD_AMT'] = $rowConstMapMenu["min_withdraw"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'day'){
						if($rowConstMapMenu["each_bank"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and DATE_FORMAT(operate_date,'%Y%M%D') = DATE_FORMAT(NOW(),'%Y%M%D')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and DATE_FORMAT(operate_date,'%Y%M%D') = DATE_FORMAT(NOW(),'%Y%M%D') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_withdraw"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'month'){
						if($rowConstMapMenu["each_bank"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and DATE_FORMAT(operate_date,'%Y%M') = DATE_FORMAT(NOW(),'%Y%M')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and DATE_FORMAT(operate_date,'%Y%M') = DATE_FORMAT(NOW(),'%Y%M') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_withdraw"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'year'){
						if($rowConstMapMenu["each_bank"] == '0'){
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and DATE_FORMAT(operate_date,'%Y') = DATE_FORMAT(NOW(),'%Y')
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':transfer_mode' => $transfer_mode
							]);
						}else{
							$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																	FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																	and DATE_FORMAT(operate_date,'%Y') = DATE_FORMAT(NOW(),'%Y') and bank_code = :bank_code
                                                                    and result_transaction = '1' and transfer_mode = :transfer_mode");
							$getTransaction->execute([
								':deptaccount_no' => $deptaccount_no,
								':bank_code' => $bank_code,
								':transfer_mode' => $transfer_mode
							]);
						}
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_withdraw"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}
				}
			}else{
				$getConstantMapMenu = $this->con->prepare("SELECT gbc.transaction_cycle,gbc.max_numof_withdraw,gbc.max_withdraw,gbc.min_withdraw
														FROM gcmenuconstantmapping gmm 
														LEFT JOIN gcbankconstant gbc 
														ON gmm.id_bankconstant = gbc.id_bankconstant
														WHERE gmm.menu_component = :menu_component and gmm.is_use = '1'");
				$getConstantMapMenu->execute([':menu_component' => $menu_component]);
				while($rowConstMapMenu = $getConstantMapMenu->fetch(\PDO::FETCH_ASSOC)){
					if($rowConstMapMenu["transaction_cycle"] == 'time'){
						if($rowConstMapMenu["max_withdraw"] >= '0' && $amt_transfer > $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["min_withdraw"] >= '0' && $amt_transfer < $rowConstMapMenu["min_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0056";
							$arrayResult['MINWITD_AMT'] = $rowConstMapMenu["min_withdraw"];
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'day'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																and DATE_FORMAT(operate_date,'%Y%M%D') = DATE_FORMAT(NOW(),'%Y%M%D')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_withdraw"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0101";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'month'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																and DATE_FORMAT(operate_date,'%Y%M') = DATE_FORMAT(NOW(),'%Y%M')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_withdraw"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0102";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0093";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
					}else if($rowConstMapMenu["transaction_cycle"] == 'year'){
						$getTransaction = $this->con->prepare("SELECT COUNT(ref_no) as NUMOF_TRANS,SUM(amount) as SUM_AMT 
																FROM gctransaction WHERE from_account = :deptaccount_no and trans_flag = '-1'
																and DATE_FORMAT(operate_date,'%Y') = DATE_FORMAT(NOW(),'%Y')
																and result_transaction = '1' and transfer_mode = :transfer_mode");
						$getTransaction->execute([
							':deptaccount_no' => $deptaccount_no,
							':transfer_mode' => $transfer_mode
						]);
						$rowTrans = $getTransaction->fetch(\PDO::FETCH_ASSOC);
						if($rowConstMapMenu["max_numof_withdraw"] >= '0' && $rowTrans["NUMOF_TRANS"] >= $rowConstMapMenu["max_numof_withdraw"]){
							$arrayResult['RESPONSE_CODE'] = "WS0103";
							$arrayResult['RESULT'] = FALSE;
							return $arrayResult;
						}
						if($rowConstMapMenu["max_withdraw"] >= '0' && $rowTrans["SUM_AMT"] + $amt_transfer >= $rowConstMapMenu["max_withdraw"]){
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
	public function getSequestAmt($deptaccount_no){
		$arrSequest = array();
		$getSequestAmt = $this->conms->prepare("SELECT SEQUEST_STATUS,SEQUEST_AMOUNT FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$getSequestAmt->execute([':deptaccount_no' => $deptaccount_no]);
		$rowSeqAmt = $getSequestAmt->fetch(\PDO::FETCH_ASSOC);
		if(isset($rowSeqAmt["SEQUEST_STATUS"])){
			if($rowSeqAmt["SEQUEST_STATUS"] == '1'){ // อายัติจำนวนเงิน
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = $rowSeqAmt["SEQUEST_AMOUNT"];
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '2'){ // อายัติการเคลื่อนไหว
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '3'){ // อายัติรอปิดบัญชี
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '4'){ // อายัติห้ามถอน/ปิดบัญชี
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '5'){ // อายัติห้ามฝาก
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '9'){ // อายัติเพื่อ ATM
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = $rowSeqAmt["SEQUEST_AMOUNT"];
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '0'){ // ไม่อายัติ
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = $rowSeqAmt["SEQUEST_AMOUNT"];
			}else{
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}
		}else{
			$arrSequest["CAN_WITHDRAW"] = TRUE;
			$arrSequest["CAN_DEPOSIT"] = TRUE;
			$arrSequest["SEQUEST_AMOUNT"] = 0;
		}
		return $arrSequest;
	}
	public function getConstantAcc($deptaccount_no){
		$getConst = $this->conms->prepare("SELECT dpm.MEMBER_NO,dpm.DEPTCLOSE_STATUS,dpt.DEPTGROUP_CODE,dpm.DEPTTYPE_CODE,dpm.DEPTACCOUNT_NAME,dpm.PRNCBAL,dpt.MINPRNCBAL,dpm.MEMBCAT_CODE,
											dpt.MINWITD_AMT,dpt.MINDEPT_AMT,ISNULL(dpt.s_maxwitd_inmonth,0) as MAXWITHD_INMONTH,ISNULL(dpt.withcount_flag,0) as IS_CHECK_PENALTY,
											dpt.LIMITDEPT_FLAG,dpt.LIMITDEPT_AMT,dpt.MAXBALANCE,dpt.MAXBALANCE_FLAG,dpm.LASTCALINT_DATE,dpm.WITHDRAWABLE_AMT,dpm.CHECKPEND_AMT
											,ISNULL(dpt.s_period_inmonth,1) as PER_PERIOD_INCOUNT,ISNULL(dpt.withcount_unit,1) as PERIOD_UNIT_CHECK
											FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.DEPTTYPE_CODE  = dpt.DEPTTYPE_CODE and dpm.MEMBCAT_CODE = dpt.MEMBCAT_CODE
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
				$queryCheckPeriod = "and CONVERT(VARCHAR(6),dps.operate_date,112) BETWEEN ".$monthCheck." and CONVERT(VARCHAR(6), GETDATE(), 112)";
			}else if($dataConst["PERIOD_UNIT_CHECK"] == '2'){
				$thisMonth = date('m');
				if($thisMonth >= 1 && $thisMonth <= 3){
					$queryCheckPeriod = "CONVERT(VARCHAR(6),dps.operate_date,112) BETWEEN CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'01') and CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'03')";
				}else if($thisMonth >= 4 && $thisMonth <= 6){
					$queryCheckPeriod = "CONVERT(VARCHAR(6),dps.operate_date,112) BETWEEN CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'04') and CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'06')";
				}else if($thisMonth >= 7 && $thisMonth <= 9){
					$queryCheckPeriod = "CONVERT(VARCHAR(6),dps.operate_date,112) BETWEEN CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'07') and CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'09')";
				}else{
					$queryCheckPeriod = "CONVERT(VARCHAR(6),dps.operate_date,112) BETWEEN CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'10') and CONCAT(CONVERT(VARCHAR(4), GETDATE(), 112),'12')";
				}
			}else if($dataConst["PERIOD_UNIT_CHECK"] == '3'){
				$monthCheck = date('Y',strtotime('-'.($dataConst["PER_PERIOD_INCOUNT"]-1).' years'));
				$queryCheckPeriod = "and CONVERT(VARCHAR(4),dps.operate_date,112) BETWEEN ".$monthCheck." and CONVERT(VARCHAR(4), GETDATE(), 112)";
			}else if($dataConst["PERIOD_UNIT_CHECK"] == '4'){
				$queryCheckPeriod = "";
			}else{
				$queryCheckPeriod = "";
			}
		}
		$checkItemIsCount = $this->conms->prepare("SELECT COUNT(*) as IS_NOTCOUNT FROM dpucfwithncount 
												WHERE depttype_code = :depttype_code and deptitem_code = :itemtype");
		$checkItemIsCount->execute([
			':depttype_code' => $dataConst["DEPTTYPE_CODE"],
			':itemtype' => $itemtype
		]);
		$rowItemCount = $checkItemIsCount->fetch(\PDO::FETCH_ASSOC);
		if($rowItemCount["IS_NOTCOUNT"] > 0){
			$getCountTrans = $this->conms->prepare("SELECT COUNT(dps.SEQ_NO) as C_TRANS FROM dpdeptstatement dps
												WHERE dps.deptaccount_no = :deptaccount_no and SUBSTRING(deptitemtype_code,1,1) <> 'D' 
												and dps.DEPTITEMTYPE_CODE NOT IN(SELECT deptitem_code FROM dpucfwithncount WHERE depttype_code = :depttype_code and membcat_code = :membcat_code)
												and dps.deptitemtype_code <> :itemtype_code and dps.item_status = '1' ".$queryCheckPeriod);
			$getCountTrans->execute([
				':deptaccount_no' => $deptaccount_no,
				':depttype_code' => $dataConst["DEPTTYPE_CODE"],
				':itemtype_code' => $itemtype,
				':membcat_code' => $dataConst['MEMBCAT_CODE']
			]);
		}else{
			$getCountTrans = $this->conms->prepare("SELECT COUNT(dps.SEQ_NO) as C_TRANS FROM dpdeptstatement dps
												WHERE dps.deptaccount_no = :deptaccount_no and SUBSTRING(deptitemtype_code,1,1) <> 'D'
												and dps.DEPTITEMTYPE_CODE NOT IN(SELECT deptitem_code FROM dpucfwithncount WHERE depttype_code = :depttype_code and membcat_code = :membcat_code)
												and dps.item_status = '1' ".$queryCheckPeriod);
			$getCountTrans->execute([
				':deptaccount_no' => $deptaccount_no,
				':depttype_code' => $dataConst["DEPTTYPE_CODE"],
				':membcat_code' => $dataConst['MEMBCAT_CODE']
			]);
		}
		$rowCountTrans = $getCountTrans->fetch(\PDO::FETCH_ASSOC);
		$count_trans = $rowCountTrans["C_TRANS"] + 1;
		if($count_trans > $dataConst["MAXWITHD_INMONTH"]){
			$getContDeptTypeFee = $this->conms->prepare("SELECT CHARGE_FLAG,s_chrg_amt1 as MIN_FEE,s_chrg_perc1 as PERCENT_FEE,s_chrg_amt2 as MAX_FEE 
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
	public function getVcMapID($depttype_code,$sys_code='DEP',$itemtype_code='DEP'){
		$getvc = $this->conms->prepare("SELECT ACCOUNT_ID FROM VCMAPACCID WHERE SYSTEM_CODE = ? AND SLIPITEMTYPE_CODE = ? AND SHRLONTYPE_CODE = ?");
		$getvc->execute([$sys_code,$itemtype_code,$depttype_code]);
		$rowvc = $getvc->fetch(\PDO::FETCH_ASSOC);
		return $rowvc;
	}
	public function getLastSeqNo($deptaccount_no){
		$getLastSEQ = $this->conms->prepare("SELECT MAX(SEQ_NO) as MAX_SEQ_NO FROM dpdeptstatement WHERE deptaccount_no = :deptaccount_no");
		$getLastSEQ->execute([':deptaccount_no' => $deptaccount_no]);
		$rowLastSEQ = $getLastSEQ->fetch(\PDO::FETCH_ASSOC);
		return $rowLastSEQ;
	}
	public function generateDocNo($component,$lib){
		$getLastDpSlipNo = $this->conms->prepare("SELECT DOCUMENT_FORMAT,DOCUMENT_PREFIX,LAST_DOCUMENTNO,DOCUMENT_YEAR,DOCUMENT_LENGTH
												FROM cmdocumentcontrol where document_code = :component");
		$getLastDpSlipNo->execute([':component' => $component]);
		$rowLastSlip = $getLastDpSlipNo->fetch(\PDO::FETCH_ASSOC);
		$deptslip_no = '';
		$lastdocument_no = $rowLastSlip["LAST_DOCUMENTNO"];
		$countPrefix = substr_count($rowLastSlip["DOCUMENT_FORMAT"],'P',0);
		$countYear = substr_count($rowLastSlip["DOCUMENT_FORMAT"],'Y',0);
		$countRunning = substr_count($rowLastSlip["DOCUMENT_FORMAT"],'R',0);
		$arrPosString = array();
		$arrPosString["P"] = strpos($rowLastSlip["DOCUMENT_FORMAT"] , 'P' , 0);
		$arrPosString["Y"] = strpos($rowLastSlip["DOCUMENT_FORMAT"] , 'Y' , 0);
		$arrPosString["R"] = strpos($rowLastSlip["DOCUMENT_FORMAT"] , 'R' , 0);
		asort($arrPosString);
		foreach($arrPosString as $key => $value){
			if($key == 'P'){
				$deptslip_no .= $lib->mb_str_pad($rowLastSlip["DOCUMENT_PREFIX"],$countPrefix);
			}else if($key == 'Y'){
				$deptslip_no .= substr($rowLastSlip["DOCUMENT_YEAR"],$countYear*-1);
			}else if($key == 'R'){
				$deptslip_no .= strtolower($lib->mb_str_pad($rowLastSlip["LAST_DOCUMENTNO"] + 1,$countRunning));
			}
		}
		$arrayResult["SLIP_NO"] = $deptslip_no;
		$arrayResult["QUERY"] = $rowLastSlip;
		return $arrayResult;
	}
	public function getConstPayType($itemtype){
		$getConstPay = $this->conms->prepare("SELECT group_itemtype as GRP_ITEMTYPE,MONEYTYPE_SUPPORT 
											FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype");
		$getConstPay->execute([':itemtype' => $itemtype]);
		$rowConstPay = $getConstPay->fetch(\PDO::FETCH_ASSOC);
		return $rowConstPay;
	}
	public function DepositMoneyInside($conmssql,$deptaccount_no,$tofrom_accid,$itemtype_dpt,$amt_transfer,$penalty_amt,
	$operate_date,$config,$log,$from_account_no,$payload,$deptslip,$lib,$max_seqno,$menu_component,$ref_no,$is_transfer=false,$slipWithdraw=null,$bank_code=null,$sigma_key=null,$constToAcc=null){
		if(empty($constToAcc)){
			$constToAcc = $this->getConstantAcc($deptaccount_no);
		}
		$rowDepPayDest = $this->getConstPayType($itemtype_dpt);
		if($constToAcc["LIMITDEPT_FLAG"] == '1' && $amt_transfer > $constToAcc["LIMITDEPT_AMT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0093';
			if($menu_component == 'TransactionDeposit'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $operate_date,
					':sigma_key' => $sigma_key,
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'ยอดทำรายการมากกว่ายอดทำรายการสูงสุดต่อครั้ง / '.$constToAcc["LIMITDEPT_AMT"]
				];
				$log->writeLog('deposittrans',$arrayStruc);
			}else{
				if($menu_component == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ยอดทำรายการมากกว่ายอดทำรายการสูงสุดต่อครั้ง '.$constToAcc["LIMITDEPT_AMT"]
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ยอดทำรายการมากกว่ายอดทำรายการสูงสุดต่อครั้ง '.$constToAcc["LIMITDEPT_AMT"]
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
			}
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($constToAcc["MAXBALANCE_FLAG"] == '1' && $amt_transfer + $constToAcc["PRNCBAL"] > $constToAcc["MAXBALANCE"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0093';
			if($menu_component == 'TransactionDeposit'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $operate_date,
					':sigma_key' => $sigma_key,
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'ยอดคงเหลือหลังทำรายการฝากฝากกว่ายอดที่สหกรณ์กำหนด / '.$constToAcc["MAXBALANCE"]
				];
				$log->writeLog('deposittrans',$arrayStruc);
			}else{
				if($menu_component == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ยอดคงเหลือหลังทำรายการฝากฝากกว่ายอดที่สหกรณ์กำหนด '.$constToAcc["MAXBALANCE"]
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ยอดคงเหลือหลังทำรายการฝากฝากกว่ายอดที่สหกรณ์กำหนด '.$constToAcc["MAXBALANCE"]
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
			}
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		if($amt_transfer < $constToAcc["MINDEPT_AMT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0056';
			if($menu_component == 'TransactionDeposit'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $operate_date,
					':sigma_key' => $sigma_key,
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'ทำรายการต่ำกว่ายอดฝากที่กำหนด  ยอดขั้นต่ำคือ/ '.$constToAcc["MINDEPT_AMT"]
				];
				$log->writeLog('deposittrans',$arrayStruc);
			}else{
				if($menu_component == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ทำรายการต่ำกว่ายอดฝากที่กำหนด ยอดขั้นต่ำคือ '.$constToAcc["MINDEPT_AMT"]
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ทำรายการต่ำกว่ายอดฝากที่กำหนด ยอดขั้นต่ำคือ '.$constToAcc["MINDEPT_AMT"]
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
			}
			$arrayResult["MINDEPT_AMT"] = $constToAcc["MINDEPT_AMT"];
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		$lastStmDestNo = $max_seqno + 1;
		$deptslip_no = $deptslip;
		$arrExecuteDest = [
			$deptslip_no,$config["COOP_ID"],$deptaccount_no,$constToAcc["DEPTTYPE_CODE"],$config["COOP_ID"],$constToAcc["DEPTGROUP_CODE"],$constToAcc["MEMBCAT_CODE"],
			$itemtype_dpt,$amt_transfer,$rowDepPayDest["MONEYTYPE_SUPPORT"],$constToAcc["PRNCBAL"],$constToAcc["WITHDRAWABLE_AMT"],
			$constToAcc["CHECKPEND_AMT"],$operate_date,$lastStmDestNo,$itemtype_dpt,date('Y-m-d H:i:s',strtotime($constToAcc["LASTCALINT_DATE"])),$penalty_amt,
			$tofrom_accid,$slipWithdraw ?? null,$amt_transfer,$operate_date
		];
		if($menu_component == 'TransactionDeposit'){
			$insertDpSlipDest = $conmssql->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,
												deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
												PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
												DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,OTHER_AMT,
												NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,REFER_SLIPNO,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
												POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
												TELLER_FLAG,OPERATE_TIME) 
												VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,
												CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),1,?,0,0,?,2,?,0,0,
												?,0,0,0,1,1,0,1,1,CONVERT(VARCHAR(19),?,20))");

		}else{
			$insertDpSlipDest = $conmssql->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,
												deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
												PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
												DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,OTHER_AMT,
												NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,REFER_SLIPNO,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
												POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
												TELLER_FLAG,OPERATE_TIME) 
												VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,
												CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),1,?,0,0,?,2,?,0,0,
												?,1,0,0,1,1,0,1,1,CONVERT(VARCHAR(19),?,20))");
		}
		if($insertDpSlipDest->execute($arrExecuteDest)){
			$arrExecuteStmDest = [
				$config["COOP_ID"],$deptaccount_no,$lastStmDestNo,$itemtype_dpt,$amt_transfer,$constToAcc["PRNCBAL"],
				$constToAcc["PRNCBAL"] + $amt_transfer,$operate_date,date('Y-m-d H:i:s',strtotime($constToAcc["LASTCALINT_DATE"])),
				$rowDepPayDest["MONEYTYPE_SUPPORT"],$operate_date,$deptslip_no
			];
			$insertStatementDest = $conmssql->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
													CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
													VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),?,CONVERT(VARCHAR(19),?,20),?,'1')");
			if($insertStatementDest->execute($arrExecuteStmDest)){
				$arrUpdateMasterDest = [
					$constToAcc["WITHDRAWABLE_AMT"] + $amt_transfer,$constToAcc["PRNCBAL"] + $amt_transfer,
					$operate_date,$operate_date,$lastStmDestNo,$deptaccount_no
				];
				$updateDeptMasterDest = $conmssql->prepare("UPDATE DPDEPTMASTER SET withdrawable_amt = ?,prncbal = ?,
														lastmovement_date = CONVERT(VARCHAR(10),?,20),
														lastaccess_date = CONVERT(VARCHAR(10),?,20),laststmseq_no = ?
														WHERE deptaccount_no = ?");
				if($updateDeptMasterDest->execute($arrUpdateMasterDest)){
					if(isset($bank_code)){
						
					}else{
						if(!$is_transfer){
							$insertTransactionLog = $this->con->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																		,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																		coop_slip_no,id_userlogin,ref_no_source)
																		VALUES(:ref_no,:slip_type,:from_account,:destination,'1',:amount,:penalty_amt,
																		:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
							$insertTransactionLog->execute([
								':ref_no' => $ref_no,
								':slip_type' => $itemtype_dpt,
								':from_account' => $from_account_no,
								':destination' => $deptaccount_no,
								':amount' => $amt_transfer,
								':penalty_amt' => $penalty_amt,
								':amount_receive' => $amt_transfer - $penalty_amt,
								':operate_date' => $operate_date,
								':member_no' => $payload["member_no"],
								':slip_no' => $deptslip_no,
								':id_userlogin' => $payload["id_userlogin"]
							]);
						}
					}
					$constToAcc["PRNCBAL"] = $constToAcc["PRNCBAL"] + $amt_transfer;
					$constToAcc["WITHDRAWABLE_AMT"] = $constToAcc["WITHDRAWABLE_AMT"] + $amt_transfer;
					$arrayResult['DEPTSLIP_NO'] = $deptslip_no;
					$arrayResult['MAX_SEQNO'] = $lastStmDestNo;
					$arrayResult['DATA_CONT'] = $constToAcc;
					$arrayResult['RESULT'] = TRUE;
					return $arrayResult;
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS0064';
					if($menu_component == 'TransactionDeposit'){
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $operate_date,
							':sigma_key' => $sigma_key,
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'update for deposit ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMasterDest->queryString.json_encode($arrUpdateMasterDest)
						];
						$log->writeLog('deposittrans',$arrayStruc);
					}else{
						if($menu_component == 'TransferDepInsideCoop'){
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $operate_date,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $amt_transfer,
								':penalty_amt' => $penalty_amt,
								':type_request' => '2',
								':transfer_flag' => '2',
								':destination' => $deptaccount_no,
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => 'update for deposit ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMasterDest->queryString.json_encode($arrUpdateMasterDest)
							];
						}else{
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $operate_date,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $amt_transfer,
								':penalty_amt' => $penalty_amt,
								':type_request' => '2',
								':transfer_flag' => '1',
								':destination' => $deptaccount_no,
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => 'update for deposit ลงตาราง DPDEPTMASTER ไม่ได้'.$updateDeptMasterDest->queryString.json_encode($arrUpdateMasterDest)
							];
						}
						$log->writeLog('transferinside',$arrayStruc);
					}
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
			}else{
				$arrayResult["RESPONSE_CODE"] = 'WS0064';
				if($menu_component == 'TransactionDeposit'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':sigma_key' => $sigma_key,
						':amt_transfer' => $amt_transfer,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'insert for deposit ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatementDest->queryString.json_encode($arrExecuteStmDest)
					];
					$log->writeLog('deposittrans',$arrayStruc);
				}else{
					if($menu_component == 'TransferDepInsideCoop'){
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $operate_date,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':type_request' => '2',
							':transfer_flag' => '2',
							':destination' => $to_account_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'insert for deposit ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatementDest->queryString.json_encode($arrExecuteStmDest)
						];
					}else{
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $operate_date,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':type_request' => '2',
							':transfer_flag' => '1',
							':destination' => $deptaccount_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'insert for deposit ลงตาราง DPDEPTSTATEMENT ไม่ได้'.$insertStatementDest->queryString.json_encode($arrExecuteStmDest)
						];
					}
					$log->writeLog('transferinside',$arrayStruc);
				}
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0064';
			if($menu_component == 'TransactionDeposit'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $operate_date,
					':sigma_key' => $sigma_key,
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => 'insert for deposit ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlipDest->queryString.json_encode($arrExecuteDest)
				];
				$log->writeLog('deposittrans',$arrayStruc);
			}else{
				if($menu_component == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'insert for deposit ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlipDest->queryString.json_encode($arrExecuteDest)
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $operate_date,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $amt_transfer,
						':penalty_amt' => $penalty_amt,
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $deptaccount_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'insert for deposit ลงตาราง DPDEPTSLIP ไม่ได้'.$insertDpSlipDest->queryString.json_encode($arrExecuteDest)
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
			}
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function WithdrawMoneyInside($conmssql,$deptaccount_no,$tofrom_accid,$itemtype_wtd,$amt_transfer,$penalty_amt,
	$operate_date,$config,$log,$payload,$deptslip,$lib,$max_seqno,$constFromAcc){
		$arrSlipno = $this->generateDocNo('DPSLIPNO',$lib);
		$checkSeqAmtSrc = $this->getSequestAmt($deptaccount_no,$itemtype_wtd);
		if($checkSeqAmtSrc["CAN_WITHDRAW"]){
			if($constFromAcc["MINPRNCBAL"] > $constFromAcc["PRNCBAL"] - ($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"] + $amt_transfer)){
				$arrayResult['RESPONSE_CODE'] = "WS0091";
				$arrayResult['SEQUEST_AMOUNT'] = $checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"];
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0092";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
		$deptslip_no = $deptslip;
		$lastStmSrcNo = $max_seqno + 1;
		$rowDepPay = $this->getConstPayType($itemtype_wtd);
		$arrExecute = [
			$deptslip_no,$config["COOP_ID"],$deptaccount_no,$constFromAcc["DEPTTYPE_CODE"],$config["COOP_ID"],$constFromAcc["DEPTGROUP_CODE"],$constFromAcc["MEMBCAT_CODE"],
			$itemtype_wtd,$amt_transfer,$rowDepPay["MONEYTYPE_SUPPORT"],$constFromAcc["PRNCBAL"],$constFromAcc["WITHDRAWABLE_AMT"],
			$constFromAcc["CHECKPEND_AMT"],$operate_date,$lastStmSrcNo,$itemtype_wtd,date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
			$penalty_amt,$tofrom_accid,$penalty_amt,$amt_transfer,$operate_date
		];
		if($penalty_amt > 0){
			$insertDpSlipSQL = "INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
								deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
								PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
								DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,OTHER_AMT,
								NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
								POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
								TELLER_FLAG,OPERATE_TIME) 
								VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,
								CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),1,?,0,0,?,2,0,?,?,0,0,0,1,1,0,1,1,CONVERT(VARCHAR(19),?,20))";
		}else{
			$insertDpSlipSQL = "INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
								deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
								PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
								DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,OTHER_AMT,
								NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,
								POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
								TELLER_FLAG,OPERATE_TIME) 
								VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,
								CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),1,?,0,0,?,0,0,?,?,0,0,0,1,1,0,1,1,CONVERT(VARCHAR(19),?,20))";
		}
		$insertDpSlip = $conmssql->prepare($insertDpSlipSQL);
		if($insertDpSlip->execute($arrExecute)){
			$slipWithdraw = $deptslip_no;
			$arrExecuteStm = [
				$config["COOP_ID"],$deptaccount_no,$lastStmSrcNo,$itemtype_wtd,$amt_transfer,$constFromAcc["PRNCBAL"],
				$constFromAcc["PRNCBAL"] - $amt_transfer,$operate_date,date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
				$rowDepPay["MONEYTYPE_SUPPORT"],$operate_date,$deptslip_no
			];
			$insertStatement = $conmssql->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
													CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
													VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),?,CONVERT(VARCHAR(19),?,20),?,'1')");
			if($insertStatement->execute($arrExecuteStm)){
				$arrUpdateMaster = [
					$constFromAcc["WITHDRAWABLE_AMT"] - $amt_transfer,$constFromAcc["PRNCBAL"] - $amt_transfer,
					$operate_date,$operate_date,$lastStmSrcNo,$deptaccount_no
				];
				$updateDeptMaster = $conmssql->prepare("UPDATE DPDEPTMASTER SET withdrawable_amt = ?,prncbal = ?,
														lastmovement_date = CONVERT(VARCHAR(19),?,20),
														lastaccess_date = CONVERT(VARCHAR(19),?,20),laststmseq_no = ?
														WHERE deptaccount_no = ?");
				if($updateDeptMaster->execute($arrUpdateMaster)){
					$constFromAcc["PRNCBAL"] = $constFromAcc["PRNCBAL"] - $amt_transfer;
					$constFromAcc["WITHDRAWABLE_AMT"] = $constFromAcc["WITHDRAWABLE_AMT"] - $amt_transfer;
					$arrayResult['DEPTSLIP_NO'] = $slipWithdraw;
					$arrayResult['MAX_SEQNO'] = $lastStmSrcNo;
					$arrayResult['DATA_CONT'] = $constFromAcc;
					$arrayResult['RESULT'] = TRUE;
					return $arrayResult;
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
					$arrayResult['ACTION'] = 'UPDATE DPDEPTMASTER ไม่ได้'.$updateDeptMaster->queryString."\n".json_encode($arrUpdateMaster);
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
			}else{
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['ACTION'] = 'Insert DPDEPTSTATEMENT ไม่ได้'.$insertStatement->queryString."\n".json_encode($arrExecuteStm);
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['ACTION'] = 'Insert DPDEPTSLIP ไม่ได้'.$insertDpSlip->queryString."\n".json_encode($arrExecute);
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function insertFeeTransaction($conmssql,$deptaccount_no,$tofrom_accid,$itemtype_wtd='FEE',$amt_transfer,$penalty_amt,
	$operate_date,$config,$deptslip_no,$lib,$max_seqno,$constFromAcc,$oneway_fee=false){
		$arrSlipno = $this->generateDocNo('DPSLIPNO',$lib);
		$deptslip_noPenalty = $lib->mb_str_pad($deptslip_no + 1,$arrSlipno["QUERY"]["DOCUMENT_LENGTH"],'0');
		$lastStmSrcNo = $max_seqno + 1;
		$rowDepPay = $this->getConstPayType($itemtype_wtd);
		if($oneway_fee){
			$arrExecutePenalty = [
				$deptslip_noPenalty,$config["COOP_ID"],$deptaccount_no,$constFromAcc["DEPTTYPE_CODE"],$config["COOP_ID"],$constFromAcc["DEPTGROUP_CODE"],$constFromAcc["MEMBCAT_CODE"],
				$itemtype_wtd,$penalty_amt,$rowDepPay["MONEYTYPE_SUPPORT"],$constFromAcc["PRNCBAL"],$constFromAcc["WITHDRAWABLE_AMT"],$constFromAcc["CHECKPEND_AMT"],
				$operate_date,$lastStmSrcNo,$itemtype_wtd,date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),$penalty_amt,$tofrom_accid,
				$deptslip_no,$penalty_amt,$operate_date
			];
			$insertDpSlipPenalty = $conmssql->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
														deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
														PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
														DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,OTHER_AMT,
														NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,REFER_SLIPNO,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,REFER_APP,
														POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
														TELLER_FLAG,OPERATE_TIME) 
														VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,
														?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),
														1,?,0,0,?,2,?,0,0,?,'DEP',0,0,0,1,1,0,1,1,
														CONVERT(VARCHAR(19),?,20))");
		}else{
			$arrExecutePenalty = [
				$deptslip_noPenalty,$config["COOP_ID"],$deptaccount_no,$constFromAcc["DEPTTYPE_CODE"],$config["COOP_ID"],$constFromAcc["DEPTGROUP_CODE"],$constFromAcc["MEMBCAT_CODE"],
				$itemtype_wtd,$penalty_amt,$rowDepPay["MONEYTYPE_SUPPORT"],$constFromAcc["PRNCBAL"],$constFromAcc["WITHDRAWABLE_AMT"],$constFromAcc["CHECKPEND_AMT"],
				$operate_date,$lastStmSrcNo,$itemtype_wtd,date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),$tofrom_accid,
				$deptslip_no,$penalty_amt,$operate_date
			];
			$insertDpSlipPenalty = $conmssql->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
														deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
														PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
														DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,
														NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,REFER_SLIPNO,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,REFER_APP,
														POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
														TELLER_FLAG,OPERATE_TIME) 
														VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,
														?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),
														1,0,0,?,2,?,0,0,?,'DEP',0,0,0,1,1,0,1,1,
														CONVERT(VARCHAR(19),?,20))");
		}
		if($insertDpSlipPenalty->execute($arrExecutePenalty)){
			$arrExecuteStmPenalty = [
				$config["COOP_ID"],$deptaccount_no,$lastStmSrcNo,$itemtype_wtd,$penalty_amt,$constFromAcc["PRNCBAL"],
				$constFromAcc["PRNCBAL"] - $penalty_amt,$operate_date,date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),
				$rowDepPay["MONEYTYPE_SUPPORT"],$operate_date,$deptslip_noPenalty
			];
			$insertStatementPenalty = $conmssql->prepare("INSERT INTO DPDEPTSTATEMENT(COOP_ID,DEPTACCOUNT_NO,SEQ_NO,DEPTITEMTYPE_CODE,OPERATE_DATE,DEPTITEM_AMT,BALANCE_FORWARD,PRNCBAL,ENTRY_ID,ENTRY_DATE,
													CALINT_FROM,CALINT_TO,CASH_TYPE,OPERATE_TIME,DEPTSLIP_NO,SYNC_NOTIFY_FLAG)
													VALUES(?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),
													CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),?,CONVERT(VARCHAR(19),?,20),?,'1')");
			if($insertStatementPenalty->execute($arrExecuteStmPenalty)){
				$arrUpdateMaster = [
					$constFromAcc["WITHDRAWABLE_AMT"] - $penalty_amt,$constFromAcc["PRNCBAL"] - $penalty_amt,
					$operate_date,$operate_date,$lastStmSrcNo,$deptaccount_no
				];
				$updateDeptMaster = $conmssql->prepare("UPDATE DPDEPTMASTER SET withdrawable_amt = ?,prncbal = ?,
														lastmovement_date = CONVERT(VARCHAR(19),?,20),
														lastaccess_date = CONVERT(VARCHAR(19),?,20),laststmseq_no = ?
														WHERE deptaccount_no = ?");
				if($updateDeptMaster->execute($arrUpdateMaster)){
					$arrayResult['RESULT'] = TRUE;
					return $arrayResult;
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS0037';
					$arrayResult['ACTION'] = 'UPDATE DPDEPTMASTER ไม่ได้'.$updateDeptMaster->queryString."\n".json_encode($arrUpdateMaster);
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
			}else{
				$arrayResult["RESPONSE_CODE"] = 'WS0037';
				$arrayResult['ACTION'] = 'Insert DPDEPTSTATEMENT ค่าปรับ ไม่ได้'.$insertStatementPenalty->queryString."\n".json_encode($arrExecuteStmPenalty);
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0037';
			$arrayResult['ACTION'] = 'Insert DPDEPTSLIP ค่าปรับ ไม่ได้'.$insertDpSlipPenalty->queryString."\n".json_encode($arrExecutePenalty);
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function insertFeePromotion($conmssql,$deptaccount_no,$tofrom_accid,$itemtype_wtd='FEE',$amt_transfer,$penalty_amt,
	$operate_date,$config,$deptslip_no,$lib,$max_seqno,$constFromAcc){
		$arrSlipno = $this->generateDocNo('DPSLIPNO',$lib);
		$deptslip_noPenalty = $lib->mb_str_pad($deptslip_no + 1,$arrSlipno["QUERY"]["DOCUMENT_LENGTH"],'0');
		$lastStmSrcNo = $max_seqno;
		$rowDepPay = $this->getConstPayType($itemtype_wtd);
		$arrExecutePenalty = [
			$deptslip_noPenalty,$config["COOP_ID"],$deptaccount_no,$constFromAcc["DEPTTYPE_CODE"],$config["COOP_ID"],$constFromAcc["DEPTGROUP_CODE"],$constFromAcc["MEMBCAT_CODE"],
			$itemtype_wtd,$penalty_amt,$rowDepPay["MONEYTYPE_SUPPORT"],$constFromAcc["PRNCBAL"],$constFromAcc["WITHDRAWABLE_AMT"],$constFromAcc["CHECKPEND_AMT"],
			$operate_date,$lastStmSrcNo,$itemtype_wtd,date('Y-m-d H:i:s',strtotime($constFromAcc["LASTCALINT_DATE"])),$tofrom_accid,
			$deptslip_no,$penalty_amt,$operate_date
		];
		$insertDpSlipPenalty = $conmssql->prepare("INSERT INTO DPDEPTSLIP(DEPTSLIP_NO,COOP_ID,DEPTACCOUNT_NO,DEPTTYPE_CODE,   
													deptcoop_id,DEPTGROUP_CODE,MEMBCAT_CODE,DEPTSLIP_DATE,RECPPAYTYPE_CODE,DEPTSLIP_AMT,CASH_TYPE,
													PRNCBAL,WITHDRAWABLE_AMT,CHECKPEND_AMT,ENTRY_ID,ENTRY_DATE, 
													DPSTM_NO,DEPTITEMTYPE_CODE,CALINT_FROM,CALINT_TO,ITEM_STATUS,CLOSEDAY_STATUS,
													NOBOOK_FLAG,CHEQUE_SEND_FLAG,TOFROM_ACCID,PAYFEE_METH,REFER_SLIPNO,DUE_FLAG,DEPTAMT_OTHER,DEPTSLIP_NETAMT,REFER_APP,
													POSTTOVC_FLAG,TAX_AMT,INT_BFYEAR,ACCID_FLAG,SHOWFOR_DEPT,GENVC_FLAG,PEROID_DEPT,CHECKCLEAR_STATUS,   
													TELLER_FLAG,OPERATE_TIME) 
													VALUES(?,?,?,?,?,?,?,CONVERT(VARCHAR(10),GETDATE(),20),?,
													?,?,?,?,?,'MOBILE',CONVERT(VARCHAR(10),?,20),?,?,CONVERT(VARCHAR(10),?,20),CONVERT(VARCHAR(10),GETDATE(),20),
													1,0,0,0,?,9,?,0,0,?,'DEP',0,0,0,1,1,1,0,1,1,
													CONVERT(VARCHAR(19),?,20))");
		if($insertDpSlipPenalty->execute($arrExecutePenalty)){
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0037';
			$arrayResult['ACTION'] = 'Insert DPDEPTSLIP ค่าปรับ ไม่ได้'.$insertDpSlipPenalty->queryString."\n".json_encode($arrExecutePenalty);
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
}
?>