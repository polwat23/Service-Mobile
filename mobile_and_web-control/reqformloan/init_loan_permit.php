<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != ""){
			try{
				$clientWS = new SoapClient($config["URL_LOAN"]);
				try {
					$argumentWS = [
						"member_no" => $member_no
					];
					$resultWS = $clientWS->__call("RqDividenInquiry", array($argumentWS));
					$respWS = $resultWS->RqDividenInquiryResult;
					if($respWS->responseCode == '00'){
						if($dataComing["request_amt"] < $respWS->minrequest_amt){
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS0123",
								":error_desc" => json_encode($respWS),
								":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
							];
							$log->writeLog('errorusage',$logStruc);
							$arrayResult["REVERT_VALUE"] = TRUE;
							$arrayResult["RESPONSE_CODE"] = 'WS0123';
							$arrayResult['RESPONSE_MESSAGE'] = str_replace('${minrequest_loan_amt}',number_format($respWS->minrequest_amt,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}else{
							if($dataComing["request_amt"] > $respWS->loancredit_amt){
								$filename = basename(__FILE__, '.php');
								$logStruc = [
									":error_menu" => $filename,
									":error_code" => "WS0084",
									":error_desc" => json_encode($respWS),
									":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
								];
								$log->writeLog('errorusage',$logStruc);
								$arrayResult["REVERT_VALUE"] = TRUE;
								$arrayResult["RESPONSE_CODE"] = 'WS0084';
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
							$arrayResult["RECEIVE_NET"] = $dataComing["request_amt"];
							//$arrayResult["REQUEST_AMT"] = $dataComing["request_amt"];
							$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
							$arrayResult['RESULT'] = TRUE;
							require_once('../../include/exit_footer.php');
						}
					}else{
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS0041",
							":error_desc" => json_encode($respWS),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0041';
						if($respWS->showErrorStatus){
							$arrayResult['RESPONSE_MESSAGE'] = $respWS->showErrorDesc;
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
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
			$oldBal = 0;
			$loanRequest = TRUE;
			try{
				$clientWS = new SoapClient($config["URL_LOAN"]);
				try {
					$argumentWS = [
						"member_no" => $member_no
					];
					$resultWS = $clientWS->__call("RqDividenInquiry", array($argumentWS));
					$respWS = $resultWS->RqDividenInquiryResult;
					if($respWS->responseCode == '00'){
						if($respWS->loancredit_amt < $respWS->minrequest_amt){
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS0123",
								":error_desc" => json_encode($respWS),
								":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
							];
							$log->writeLog('errorusage',$logStruc);
							$arrayResult["REVERT_VALUE"] = TRUE;
							$arrayResult["RESPONSE_CODE"] = 'WS0123';
							$arrayResult['RESPONSE_MESSAGE'] = str_replace('${minrequest_loan_amt}',number_format($respWS->minrequest_amt,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}else{
							$maxloan_amt = $respWS->loancredit_amt;
							if($maxloan_amt <= 0){
								$arrayResult['RESPONSE_CODE'] = "WS0084";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
							$arrDepositGrp = array();
							$getAccountDeposit = $conoracle->prepare("SELECT DEPTACCOUNT_NO FROM DPDEPTMASTER WHERE member_no = :member_no 
																							and deptclose_status = '0' and transonline_flag = '1' and depttype_code = '10' ");
							$getAccountDeposit->execute([':member_no' => $member_no]);
							while($rowAccDept = $getAccountDeposit->fetch(PDO::FETCH_ASSOC)){
								$arrDeposit = array();
								$arrDeposit["ACCOUNT_NO"] = $rowAccDept["DEPTACCOUNT_NO"];
								$arrDeposit["ACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccDept["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
								$arrDepositGrp[] = $arrDeposit;
							}
							$arrayResult["RECEIVE_NET"] = $respWS->loancredit_amt;
							$arrayResult["REQUEST_AMT"] = $respWS->loancredit_amt;
							$arrayResult["LOAN_PERMIT_AMT"] = $respWS->loancredit_amt;
							$arrayResult['COOP_ACCOUNT'] = $arrDepositGrp;
							$arrayResult['DISABLE_PERIOD'] = TRUE;
							$arrayResult['HIDE_PERIOD'] = TRUE;
							$arrayResult['LOANREQ_AMT_STEP'] = 100;
							$arrayResult['HIDE_PERIOD_PAYMENT'] = TRUE;
							//$arrayResult["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
							//$arrayResult["PERIOD_PAYMENT"] = $period_payment;
							$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
							$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
							$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
							$arrayOther[0]["LABEL"] = 'วันที่รับเงินกู้';
							$arrayOther[0]["VALUE"] = $lib->convertdate($respWS->loanrcvfix_date,'d M Y');
							$arrayResult['OTHER_INFO'] = $arrayOther;
							$arrayResult['RESULT'] = TRUE;
							require_once('../../include/exit_footer.php');
						}
					}else{
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS0041",
							":error_desc" => json_encode($respWS),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0041';
						if($respWS->showErrorStatus){
							$arrayResult['RESPONSE_MESSAGE'] = $respWS->showErrorDesc;
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
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