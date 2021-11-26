<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try{
			$clientWS = new SoapClient($config["URL_LOAN"]);
			try {
				$argumentWS = [
					"member_no" => $member_no,
					"ItemAmt" => $dataComing["request_amt"],
					"deptaccount_no" => $dataComing["deptaccount_no_coop"]
				];
				$resultWS = $clientWS->__call("RqDividenPayment", array($argumentWS));
				$respWS = $resultWS->RqDividenPaymentResult;
				if($respWS->responseCode == '00'){
					$reqloan_doc = $respWS->loanrequest_docno;
					$dataComing["period"] = 0;
					$dataComing["period_payment"] = 0;
					$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,receive_net,
																			int_rate_at_req,salary_at_req,id_userlogin)
																			VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:request_amt,:int_rate,:salary,:id_userlogin)");
					if($InsertFormOnline->execute([
						':reqloan_doc' => $reqloan_doc,
						':member_no' => $payload["member_no"],
						':loantype_code' => $dataComing["loantype_code"],
						':request_amt' => $dataComing["request_amt"],
						':period_payment' => $dataComing["period_payment"],
						':period' => $dataComing["period"],
						':loanpermit_amt' => $dataComing["loanpermit_amt"],
						':int_rate' => $dataComing["int_rate"] / 100,
						':salary' => null,
						':id_userlogin' => $payload["id_userlogin"]
					])){
						$arrayResult['SHOW_SLIP'] = TRUE;
						$arrayResult['APV_DOCNO'] = $reqloan_doc;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS1036",
							":error_desc" => "ขอกู้ไม่ได้เพราะ Insert ลงตาราง gcreqloan ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
								':reqloan_doc' => $reqloan_doc,
								':member_no' => $payload["member_no"],
								':loantype_code' => $dataComing["loantype_code"],
								':request_amt' => $dataComing["request_amt"],
								':period_payment' => $dataComing["period_payment"],
								':period' => $dataComing["period"],
								':loanpermit_amt' => $dataComing["loanpermit_amt"],
								':int_rate' => $dataComing["int_rate"] / 100,
								':salary' => null,
								':id_userlogin' => $payload["id_userlogin"]
							]),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$message_error = "ขอกู้ไม่ได้เพราะ Insert ลง gcreqloan ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqloan_doc' => $reqloan_doc,
							':member_no' => $payload["member_no"],
							':loantype_code' => $dataComing["loantype_code"],
							':request_amt' => $dataComing["request_amt"],
							':period_payment' => $dataComing["period_payment"],
							':period' => $dataComing["period"],
							':loanpermit_amt' => $dataComing["loanpermit_amt"],
							':int_rate' => $dataComing["int_rate"] / 100,
							':salary' => null,
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$lib->sendLineNotify($message_error);
						$arrayResult['RESPONSE_CODE'] = "WS1036";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
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