<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
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
						$arrayResult["RESPONSE_CODE"] = 'WS0123';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}else{
						$arrDeposit = array();
						$getAccountDeposit = $conoracle->prepare("SELECT DEPTACCOUNT_NO FROM DPDEPTMASTER WHERE member_no = :member_no 
																						and deptclose_status = '0' and transonline_flag = '1' and depttype_code = '10' ");
						$getAccountDeposit->execute([':member_no' => $member_no]);
						while($rowAccDept = $getAccountDeposit->fetch(PDO::FETCH_ASSOC)){
							$arrDeposit[] = $rowAccDept["DEPTACCOUNT_NO"];
						}
						$arrayResult['REQUEST_AMT'] = $respWS->loancredit_amt;
						$arrayResult['RECEIVE_AMT'] = $respWS->loancredit_amt;
						$arrayResult['ACCOUNT_DEPOSIT'] = $arrDeposit;
						$arrayResult['HIDE_PERIOD'] = TRUE;
						$arrayResult['HIDE_PERIOD_PAYMENT'] = TRUE;
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
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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