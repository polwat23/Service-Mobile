<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrLoanGrp = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_receive_loan = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
				$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
			}
			$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.withdrawable_amt as prncbal,dpm.depttype_code,dpm.SEQUEST_AMOUNT,dpt.MINPRNCBAL
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).") and dpm.deptclose_status = 0 and dpm.transonline_flag = 1
													ORDER BY dpm.deptaccount_no ASC");
			$getDataBalAcc->execute();
			while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
				$arrAccAllow = array();
				$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$formatDept);
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccAllow["DEPTACCOUNT_NO_FORMAT"],$formatDeptHidden);
				$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
				$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
				$arrAccAllow["BALANCE"] = $rowDataAccAllow["PRNCBAL"];
				$arrAccAllow["BALANCE_FORMAT"] = number_format($arrAccAllow["BALANCE"],2);
				$arrGroupAccAllow[] = $arrAccAllow;
			}
			$arrGrpLoan = array();
			$fetchAllowReceiveLoantype = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_receive = '1'");
			$fetchAllowReceiveLoantype->execute();
			while($rowLoanConst = $fetchAllowReceiveLoantype->fetch(PDO::FETCH_ASSOC)){
				$arrGrpLoan[] = $rowLoanConst["loantype_code"];
			}
			try{
				$clientWS = new SoapClient($config["URL_LOAN"]);
				$fetchLoanRepay = $conoracle->prepare("SELECT ln.LOANCONTRACT_NO,LT.LOANTYPE_CODE,LT.LOANTYPE_DESC
																		FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE
																		WHERE ln.loantype_code IN(".implode(',',$arrGrpLoan).") and ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
				$fetchLoanRepay->execute([':member_no' => $member_no]);
				while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
					try {
						$argumentWS = [
							"member_no" => $member_no,
							"loancontract_no" => $rowLoan["LOANCONTRACT_NO"]
						];
						$resultWS = $clientWS->__call("RqInquiry", array($argumentWS));
						$respWS = $resultWS->RqInquiryResult;
						if($respWS->responseCode == '00'){
							$arrLoan = array();
							$contract_no = $rowLoan["LOANCONTRACT_NO"];
							if(mb_stripos($contract_no,'.') === FALSE){
								$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
								if(mb_strlen($contract_no) == 10){
									$arrLoan["CONTRACT_NO"] = $loan_format;
								}else if(mb_strlen($contract_no) == 11){
									$arrLoan["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
								}
							}else{
								$arrLoan["CONTRACT_NO"] = $contract_no;
							}
							$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
							$arrLoan["PRN_BALANCE"] = number_format($respWS->availBalance,2);
							$arrLoan["BALANCE"] = $respWS->availBalance;
							$arrLoan["BALANCE_FORMAT"] = number_format($respWS->availBalance,2);
							$arrLoanGrp[] = $arrLoan;
						}
					}catch(Throwable $e){
					}
				}
				if(sizeof($arrGroupAccAllow) > 0){
					$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
					$arrayResult['LOAN'] = $arrLoanGrp;
					$arrayResult['IS_FEE_INFO'] = TRUE;
					$arrayResult['STEP_AMOUNT_DIGIT'] = 100;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0023";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}catch(Throwable $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0041",
					":error_desc" => "ไมสามารถต่อไปยัง Service เงินกู้ได้ "."\n"."Error => ".$e->getMessage(),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service เงินกู้ได้ "."\n"."Error => ".$e->getMessage()."\n"."DATA => ".json_encode($dataComing);
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult["RESPONSE_CODE"] = 'WS0041';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>