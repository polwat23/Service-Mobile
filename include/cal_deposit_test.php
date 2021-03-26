<?php

namespace CalculateDepTest;

use Connection\connection;
use Utility\Library;


class CalculateDepTest {
	private $con;
	private $conora;
	private $lib;
	
	function __construct() {
		$connection = new connection();
		$this->lib = new library();
		$this->con = $connection->connecttomysql();
		//$this->conora = $this->connecttooracle();
		$dbuser = 'iscotest';
		$dbpass = 'iscotest';
		$dbname = "(DESCRIPTION =
					(ADDRESS_LIST =
					  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.0.226)(PORT = 1521))
					)
					(CONNECT_DATA =
					  (SERVICE_NAME = gcoop)
					)
				  )";
		$this->conora = new \PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
		$this->conora->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
		$this->conora->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
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
		$getSequestAmt = $this->conora->prepare("SELECT SEQUEST_STATUS,SEQUEST_AMOUNT FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$getSequestAmt->execute([':deptaccount_no' => $deptaccount_no]);
		$rowSeqAmt = $getSequestAmt->fetch(\PDO::FETCH_ASSOC);
		if(isset($rowSeqAmt["SEQUEST_STATUS"])){
			if($rowSeqAmt["SEQUEST_STATUS"] == '1'){ // อายัดจำนวนเงิน
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = $rowSeqAmt["SEQUEST_AMOUNT"];
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '2'){ // อายัดการเคลื่อนไหว
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '3'){ // อายัดรอปิดบัญชี
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '4'){ // อายัดห้ามถอน/ปิดบัญชี
				$arrSequest["CAN_WITHDRAW"] = FALSE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '5'){ // อายัดห้ามฝาก
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = FALSE;
				$arrSequest["SEQUEST_AMOUNT"] = 0;
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '9'){ // อายัดเพื่อ ATM
				$arrSequest["CAN_WITHDRAW"] = TRUE;
				$arrSequest["CAN_DEPOSIT"] = TRUE;
				$arrSequest["SEQUEST_AMOUNT"] = $rowSeqAmt["SEQUEST_AMOUNT"];
			}else if($rowSeqAmt["SEQUEST_STATUS"] == '0'){ // ไม่อายัด
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
		$getConst = $this->conora->prepare("SELECT dpm.MEMBER_NO,dpm.DEPTCLOSE_STATUS,dpt.DEPTGROUP_CODE,dpm.DEPTTYPE_CODE,dpm.DEPTACCOUNT_NAME,dpm.PRNCBAL,dpt.MINPRNCBAL,
											dpt.MINWITD_AMT,dpt.MINDEPT_AMT,NVL(dpt.s_maxwitd_inmonth,0) as MAXWITHD_INMONTH,NVL(dpt.withcount_flag,0) as IS_CHECK_PENALTY,
											dpt.LIMITDEPT_FLAG,dpt.LIMITDEPT_AMT,dpt.MAXBALANCE,dpt.MAXBALANCE_FLAG,dpm.LASTCALINT_DATE,dpm.WITHDRAWABLE_AMT,dpm.CHECKPEND_AMT
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
	public function getVcMapID($depttype_code,$sys_code='DEP'){
		$getvc = $this->conora->prepare("SELECT ACCOUNT_ID FROM VCMAPACCID WHERE SYSTEM_CODE = :sys_code AND SLIPITEMTYPE_CODE = :sys_code AND SHRLONTYPE_CODE = :depttype_code");
		$getvc->execute([
			':depttype_code' => $depttype_code,
			':sys_code' => $sys_code
		]);
		$rowvc = $getvc->fetch(\PDO::FETCH_ASSOC);
		return $rowvc;
	}
	public function getLastSeqNo($deptaccount_no){
		$getLastSEQ = $this->conora->prepare("SELECT MAX(SEQ_NO) as MAX_SEQ_NO FROM dpdeptstatement WHERE deptaccount_no = :deptaccount_no");
		$getLastSEQ->execute([':deptaccount_no' => $deptaccount_no]);
		$rowLastSEQ = $getLastSEQ->fetch(\PDO::FETCH_ASSOC);
		return $rowLastSEQ;
	}
	public function generateDocNo($component,$lib){
		$getLastDpSlipNo = $this->conora->prepare("SELECT DOCUMENT_FORMAT,DOCUMENT_PREFIX,LAST_DOCUMENTNO,DOCUMENT_YEAR,DOCUMENT_LENGTH
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
		$getConstPay = $this->conora->prepare("SELECT group_itemtpe as GRP_ITEMTYPE,MONEYTYPE_SUPPORT 
											FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype");
		$getConstPay->execute([':itemtype' => $itemtype]);
		$rowConstPay = $getConstPay->fetch(\PDO::FETCH_ASSOC);
		return $rowConstPay;
	}
}
?>