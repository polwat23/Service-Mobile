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
		$this->con = $connection->connecttomysql();
		$this->conora = $connection->connecttooracle();
	}
	
	public function initDept($deptaccount_no,$amt_transfer,$itemtype,$fee_amt=0){
		$dataConst = $this->getConstantAcc($deptaccount_no);
		$penalty_amt = 0;
		if(substr($itemtype,0,1) == 'W'){
			$sumAllTransfer = ($dataConst["PRNCBAL"]) - ($penalty_amt + $fee_amt + $amt_transfer);
			if($sumAllTransfer < 0){
				$arrayResult['RESPONSE_CODE'] = "WS0100";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
			$arrayResult["DEPTACCOUNT_NAME"] = $dataConst["DEPTACCOUNT_NAME"];
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}else{
			$sumAllTransfer = ($dataConst["PRNCBAL"]) - ($penalty_amt + $fee_amt + $amt_transfer);
			if($sumAllTransfer < 0){
				$arrayResult['RESPONSE_CODE'] = "WS0100";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
			$arrayResult["DEPTACCOUNT_NAME"] = $dataConst["DEPTACCOUNT_NAME"];
			$arrayResult['RESULT'] = TRUE;
			return $arrayResult;
		}
		
	}
	public function getWithdrawable($deptaccount_no){
		$DataAcc = $this->getConstantAcc($deptaccount_no);
		return $DataAcc["PRNCBAL"] - $DataAcc["MINPRNCBAL"];
	}
	public function depositCheckDepositRights($deptaccount_no,$amt_transfer,$menu_component,$bank_code=null,$check_allow_acc = true){
		$dataConst = $this->getConstantAcc($deptaccount_no);
		if($dataConst["MAXBALANCE_FLAG"] == '1' && $dataConst["MAXBALANCE"] > 0){
			if($dataConst["PRNCBAL"] + $amt_transfer > $dataConst["MAXBALANCE"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}
		if($dataConst["ACC_STATUS"] != 'O'){
			$arrayResult['RESPONSE_CODE'] = "WS0089";
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['dataConst'] = $deptaccount_no;
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
			$menucheckrights = "and gca.allow_buyshare = '1'";
			$transfer_mode = "3";
		}else if($menu_component == 'TransferDepPayLoan'){
			$menucheckrights = "and gca.allow_payloan = '1'";
			$transfer_mode = "2";
		}
		if($check_allow_acc){
			$checkUserAllow = $this->con->prepare("SELECT gua.is_use,gua.limit_transaction_amt FROM gcuserallowacctransaction gua 
													LEFT JOIN gcconstantaccountdept gca ON gua.id_accountconstant = gca.id_accountconstant
													WHERE gua.deptaccount_no = :deptaccount_no and gua.is_use = '1' ".$menucheckrights);
			$checkUserAllow->execute([':deptaccount_no' => $deptaccount_no]);
		}else{
			$checkUserAllow = $this->con->prepare("SELECT 1 as is_use, 999999999.00 as limit_transaction_amt FROM gcconstantaccountdept gca
													WHERE gca.dept_type_code = :dept_type_code ".$menucheckrights);
			$checkUserAllow->execute([':dept_type_code' => $dataConst["DEPTTYPE_CODE"]]);
		}
		$rowUserAllow = $checkUserAllow->fetch(\PDO::FETCH_ASSOC);
		if($rowUserAllow["is_use"] == "1"){
			if($amt_transfer > $rowUserAllow["limit_transaction_amt"]){
				$arrayResult['RESPONSE_CODE'] = "WS0093";
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
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
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	public function depositCheckWithdrawRights($deptaccount_no,$amt_transfer,$menu_component,$bank_code=null){
		$dataConst = $this->getConstantAcc($deptaccount_no);
		if($dataConst["ACC_STATUS"] != 'O'){
			$arrayResult['RESPONSE_CODE'] = "WS0089";
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
			$menucheckrights = "and gca.allow_buyshare = '1'";
			$transfer_mode = "3";
		}else if($menu_component == 'TransferDepPayLoan'){
			$menucheckrights = "and gca.allow_payloan = '1'";
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
		$arrSequest["CAN_WITHDRAW"] = TRUE;
		$arrSequest["CAN_DEPOSIT"] = TRUE;
		$arrSequest["SEQUEST_AMOUNT"] = 0;
		return $arrSequest;
	}

	public function getConstantAcc($deptaccount_no){
		$getConst = $this->conora->prepare("SELECT dpm.account_id as MEMBER_NO,dpm.ACC_STATUS,dpm.ACC_TYPE as DEPTTYPE_CODE,dpm.account_name as DEPTACCOUNT_NAME,
											dpm.BALANCE as PRNCBAL,dpt.min_bal as MINPRNCBAL,dpm.LAST_PAGE,dpm.LAST_LINE,dpm.LINE_CARD,dpm.PAGE_CARD,
											dpt.min_wdl as MINWITD_AMT,dpt.min_dep as MINDEPT_AMT
											FROM BK_H_SAVINGACCOUNT dpm LEFT JOIN BK_M_ACC_TYPE dpt ON dpm.ACC_TYPE  = dpt.ACC_TYPE
											WHERE dpm.account_no = :deptaccount_no");
		$getConst->execute([':deptaccount_no' => $deptaccount_no]);
		$rowConst = $getConst->fetch(\PDO::FETCH_ASSOC);
		return $rowConst;
	}
	
	public function getLastSeqNo($deptaccount_no){
		$getLastSEQ = $this->conora->prepare("SELECT MAX(BOOK_ID) as MAX_SEQ_NO FROM BK_T_NOBOOK WHERE account_no = :deptaccount_no");
		$getLastSEQ->execute([':deptaccount_no' => $deptaccount_no]);
		$rowLastSEQ = $getLastSEQ->fetch(\PDO::FETCH_ASSOC);
		return $rowLastSEQ;
	}
	
	public function generateDocNo($bill_id,$lib){
		$getLastDpSlipNo = $this->conora->prepare("select bill_type,bill_running,bill_year from sys_bill where bill_id = :bill_id");
		$getLastDpSlipNo->execute([':bill_id' => $bill_id]);
		$rowLastSlip = $getLastDpSlipNo->fetch(\PDO::FETCH_ASSOC);
		$bill_running = $lib->mb_str_pad(($rowLastSlip["BILL_RUNNING"]+1), 6);
		$deptslip_no = '';
		$deptslip_no = $rowLastSlip["BILL_YEAR"].$rowLastSlip["BILL_TYPE"].($bill_running);
		$arrayResult["SLIP_NO"] = $deptslip_no;
		$arrayResult["BILL_RUNNING"] = $rowLastSlip["BILL_RUNNING"]+1;
		$arrayResult["QUERY"] = $rowLastSlip;
		return $arrayResult;
	}

	
	public function DepositMoneyInside($conoracle,$deptaccount_no,$tran_code,$amt_transfer,$from_account_no,$payload,$menu_component,$log,$ref_no,$is_bill=false){
		$constToAcc = $this->getConstantAcc($deptaccount_no);
		$operate_date = date('Y-m-d H:i:s');
		$page_no =  $constToAcc["LAST_LINE"] <= 24 ? $constToAcc["LAST_PAGE"] : $constToAcc["LAST_PAGE"] + 1;
		$line_no = $constToAcc["LAST_LINE"] < 24 ? $constToAcc["LAST_LINE"] + 1 : 1;
		$page_card = $constToAcc["LINE_CARD"] <= 24 ? $constToAcc["PAGE_CARD"] : $constToAcc["PAGE_CARD"] + 1;
		$line_card = $constToAcc["LINE_CARD"] < 24 ? $constToAcc["LINE_CARD"] + 1 : 1;
		$penalty_amt = 0;
		$arrExecute = [
			':account_no' => $deptaccount_no,
			':tran_code' => $tran_code,
			':page_no' => $page_no,
			':line_no' => $line_no,
			':amt_transfer' => $amt_transfer,
			':balance' => $constToAcc["PRNCBAL"] + $amt_transfer,
			':line_card' => $line_card,
			':page_card' => $page_card,
			':br_no' => SUBSTR($deptaccount_no,0,3),
			':ref_no' => $ref_no
		];
		$insertStatementSQL = "INSERT INTO BK_T_NOBOOK(CURR_DATE,ACCOUNT_NO,NOBOOK_SEQ,TRANS_CODE,USER_ID,DEP_CASH,DEP_CHQ,FLG_PRN,LINE_NO,
							PAGE_NO,T_TRNS_TYPE,NOBOOK_CAUSE,BACK_DATE,WDL_CASH,WDL_CHQ,NOBOOK_DOC,N_BALANCE,
							NO_FIX_NO,LINE_CARD,PAGE_CARD,FLG_CARD,BR_NO_REC,REF_NUM) 
							VALUES(SYSDATE,:account_no,1,:tran_code,'APP01',:amt_transfer,0,'N',:line_no,:page_no,'R','',SYSDATE,0,0,'',
							:balance,'0',:line_card,:page_card,'N',:br_no,:ref_no)";
		$insertStatement = $conoracle->prepare($insertStatementSQL);
		if($insertStatement->execute($arrExecute)){
			$arrExecuteMaster = [
				':account_no' => $deptaccount_no,
				':page_no' => $page_no,
				':line_no' => $line_no,
				':amt_transfer' => $amt_transfer,
				':balance' => $constToAcc["PRNCBAL"] + $amt_transfer,
				':line_card' => $line_card,
				':page_card' => $page_card
			];
			$updateMasterSQL = "UPDATE BK_H_SAVINGACCOUNT SET LAST_DATE = SYSDATE,
								LAST_DEP = :amt_transfer,LAST_PAGE = :page_no,LAST_LINE = :line_no,BALANCE = :balance,AVAILABLE = :balance,
								LINE_CARD = :line_card,PAGE_CARD = :page_card WHERE ACCOUNT_NO = :account_no";
			$updateMaster = $conoracle->prepare($updateMasterSQL);
			if($updateMaster->execute($arrExecuteMaster)){
				$arrExecuteFinance = [
					':account_no' => $deptaccount_no,
					':tran_code' => $tran_code,
					':amt_transfer' => $amt_transfer,
					':balance' => $constToAcc["PRNCBAL"] + $amt_transfer,
					':page_card' => $page_card
				];
				$insertFinanceSQL = "INSERT INTO BK_T_FINANCE(F_DATE,USER_ID,F_SEQ,F_TIME,F_FROM_ACC,TRANS_CODE,F_TRANS_TYPE,
									F_DEP,F_WDL,F_EROROR,FI_FIX_NO,F_BALANCE,F_BRNO) 
									VALUES (SYSDATE,'APP01',:page_card,SYSDATE,
									:account_no,:tran_code,'R',:amt_transfer,0,'N',0,:balance,'121')";
				$insertFinance = $conoracle->prepare($insertFinanceSQL);
				if($insertFinance->execute($arrExecuteFinance)){
					if(!$is_bill){
						$insertTransactionLog = $this->con->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	coop_slip_no,id_userlogin,ref_no_source)
																	VALUES(:ref_no,:slip_type,:from_account,:destination,'1',:amount,:penalty_amt,
																	:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
						$insertTransactionLog->execute([
							':ref_no' => $ref_no,
							':slip_type' => $tran_code,
							':from_account' => $from_account_no,
							':destination' => $deptaccount_no,
							':amount' => $amt_transfer,
							':penalty_amt' => $penalty_amt,
							':amount_receive' => $amt_transfer - $penalty_amt,
							':operate_date' => $operate_date,
							':member_no' => $payload["member_no"],
							':slip_no' => null,
							':id_userlogin' => $payload["id_userlogin"]
						]);
					}
					$arrayResult['RESULT'] = TRUE;
					return $arrayResult;
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS0064';
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
							':response_message' => 'insert for deposit ลงตาราง BK_T_FINANCE ไม่ได้'.$insertFinance->queryString.json_encode($arrExecuteFinance)
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
							':response_message' => 'insert for deposit ลงตาราง BK_T_FINANCE ไม่ได้'.$insertFinance->queryString.json_encode($arrExecuteFinance)
						];
					}
					$log->writeLog('transferinside',$arrayStruc);
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
			}else{
				$arrayResult["RESPONSE_CODE"] = 'WS0064';
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
						':response_message' => 'update for deposit ลงตาราง BK_H_SAVINGACCOUNT ไม่ได้'.$updateMaster->queryString.json_encode($arrExecuteMaster)
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
						':response_message' => 'update for deposit ลงตาราง BK_H_SAVINGACCOUNT ไม่ได้'.$updateMaster->queryString.json_encode($arrExecuteMaster)
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0064';
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
					':response_message' => 'insert for deposit ลงตาราง bk_t_nobook ไม่ได้'.$insertStatement->queryString.json_encode($arrExecute)
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
					':response_message' => 'insert for deposit ลงตาราง bk_t_nobook ไม่ได้'.json_encode($insertStatement->errorInfo())
				];
			}
			$log->writeLog('transferinside',$arrayStruc);
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
	
	public function WithdrawMoneyInside($conoracle,$deptaccount_no,$tran_code,$amt_transfer,$prevData=null,$ref_no){
		$constAccount = $this->getConstantAcc($deptaccount_no);
		$page_no =  $constAccount["LAST_LINE"] <= 24 ? $constAccount["LAST_PAGE"] : $constAccount["LAST_PAGE"] + 1;
		$line_no = $constAccount["LAST_LINE"] < 24 ? $constAccount["LAST_LINE"] + 1 : 1;
		$page_card = $constAccount["LINE_CARD"] <= 24 ? $constAccount["PAGE_CARD"] : $constAccount["PAGE_CARD"] + 1;
		$line_card = $constAccount["LINE_CARD"] < 24 ? $constAccount["LINE_CARD"] + 1 : 1;
		if(isset($prevData)){
			$constAccount["PRNCBAL"] = $prevData["PRNCBAL"];
			$page_no =  $prevData["LAST_LINE_NO"] <= 24 ? $prevData["LAST_PAGE_NO"] : $prevData["LAST_PAGE_NO"] + 1;
			$line_no = $prevData["LAST_LINE_NO"] < 24 ? $prevData["LAST_LINE_NO"] + 1 : 1;
			$page_card = $prevData["LAST_LINE_CARD"] <= 24 ? $prevData["LAST_PAGE_CARD"] : $prevData["LAST_PAGE_CARD"] + 1;
			$line_card = $prevData["LAST_LINE_CARD"] < 24 ? $prevData["LAST_LINE_CARD"] + 1 : 1;
		}
		$arrExecute = [
			':account_no' => $deptaccount_no,
			':tran_code' => $tran_code,
			':page_no' => $page_no,
			':line_no' => $line_no,
			':amt_transfer' => $amt_transfer,
			':balance' => $constAccount["PRNCBAL"] - $amt_transfer,
			':line_card' => $line_card,
			':page_card' => $page_card,
			':br_no' => SUBSTR($deptaccount_no,0,3),
			':ref_no' => $ref_no
		];
		$insertStatementSQL = "INSERT INTO BK_T_NOBOOK(CURR_DATE,ACCOUNT_NO,NOBOOK_SEQ,TRANS_CODE,USER_ID,DEP_CASH,DEP_CHQ,FLG_PRN,LINE_NO,
							PAGE_NO,T_TRNS_TYPE,NOBOOK_CAUSE,BACK_DATE,WDL_CASH,WDL_CHQ,NOBOOK_DOC,N_BALANCE,
							NO_FIX_NO,LINE_CARD,PAGE_CARD,FLG_CARD,BR_NO_REC,REF_NUM) 
							VALUES(SYSDATE,:account_no,1,:tran_code,'APP01',0,0,'N',:line_no,:page_no,'R','',SYSDATE,:amt_transfer,0,'',
							:balance,'0',:line_card,:page_card,'N',:br_no,:ref_no)";
		$insertStatement = $conoracle->prepare($insertStatementSQL);
		if($insertStatement->execute($arrExecute)){
			$arrExecuteMaster = [
				':account_no' => $deptaccount_no,
				':page_no' => $page_no,
				':line_no' => $line_no,
				':amt_transfer' => $amt_transfer,
				':balance' => $constAccount["PRNCBAL"] - $amt_transfer,
				':line_card' => $line_card,
				':page_card' => $page_card
			];
			$updateMasterSQL = "UPDATE BK_H_SAVINGACCOUNT SET LAST_DATE = SYSDATE,
								LAST_WDL = :amt_transfer,LAST_PAGE = :page_no,LAST_LINE = :line_no,BALANCE = :balance,AVAILABLE = :balance,
								LINE_CARD = :line_card,PAGE_CARD = :page_card WHERE ACCOUNT_NO = :account_no";
			$updateMaster = $conoracle->prepare($updateMasterSQL);
			if($updateMaster->execute($arrExecuteMaster)){
				$arrExecuteFinance = [
					':account_no' => $deptaccount_no,
					':tran_code' => $tran_code,
					':amt_transfer' => $amt_transfer,
					':balance' => $constAccount["PRNCBAL"] - $amt_transfer,
					':page_card' => $page_card
				];
				$insertFinanceSQL = "INSERT INTO BK_T_FINANCE(F_DATE,USER_ID,F_SEQ,F_TIME,F_FROM_ACC,TRANS_CODE,F_TRANS_TYPE,
									F_DEP,F_WDL,F_EROROR,FI_FIX_NO,F_BALANCE,F_BRNO) 
									VALUES (SYSDATE,'APP01',:page_card,SYSDATE,
									:account_no,:tran_code,'R',0,:amt_transfer,'N',0,:balance,'121')";
				$insertFinance = $conoracle->prepare($insertFinanceSQL);
				if($insertFinance->execute($arrExecuteFinance)){
					$arrayResult['LAST_PAGE_CARD'] = $page_card;
					$arrayResult['LAST_LINE_CARD'] = $line_card;
					$arrayResult['LAST_PAGE_NO'] = $page_no;
					$arrayResult['LAST_LINE_NO'] = $line_no;
					$arrayResult["PRNCBAL"] = $constAccount["PRNCBAL"] - $amt_transfer;
					$arrayResult['RESULT'] = TRUE;
					return $arrayResult;
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
					$arrayResult['ACTION'] = 'INSERT BK_T_FINANCE ไม่ได้'.$insertFinance->queryString."\n".json_encode($arrExecuteFinance);
					$arrayResult['RESULT'] = FALSE;
					return $arrayResult;
				}
			}else{
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['ACTION'] = 'UPDATE BK_H_SAVINGACCOUNT ไม่ได้'.$updateMaster->queryString."\n".json_encode($arrExecute);
				$arrayResult['RESULT'] = FALSE;
				return $arrayResult;
			}
		}else{
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['ACTION'] = 'Insert BK_T_NOBOOK ไม่ได้'.$insertStatement->queryString."\n".json_encode($arrExecute);
			$arrayResult['RESULT'] = FALSE;
			return $arrayResult;
		}
	}
}
?>