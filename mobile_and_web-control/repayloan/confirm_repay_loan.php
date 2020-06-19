<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loancontract_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			try {
				$arrayGroup = array();
				$arrayGroup["coop_id"] = $config["COOP_ID"];
				$arrayGroup["loancontract_no"] = $dataComing["loancontract_no"];
				$arrayGroup["member_no"] = $member_no;
				$arrayGroup["operate_date"] = date('c');
				$arrayGroup["slip_date"] = date('c');
				$arrayGroup["entry_id"] = "mobile_app";
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lninitloans" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_initslippayin_mobile", array($argumentWS));
				$responseLn = $resultWS->of_initslippayin_mobileResult;
				$arrayResult['BF_INT'] = $responseLn->bfintarrset_amt;
				$arrayResult['INT_PERIOD'] = $responseLn->interest_period;
				$arrayResult['BF_BAL'] = $responseLn->bfshrcont_balamt;
				$arrayResult['PERIOD'] = $responseLn->period;
				$arrayResult['PAYMENT_INT'] = $responseLn->bfintarrset_amt + $responseLn->interest_period;
				$arrayResult['PAYMENT_PRIN'] = $dataComing["amt_transfer"] - $arrayResult['PAYMENT_INT'];
				if($arrayResult['PAYMENT_PRIN'] < 0){
					$arrayResult['PAYMENT_PRIN'] = 0;
				}
				if($arrayResult['PAYMENT_INT'] > $dataComing["amt_transfer"]){
					$arrayResult['PAYMENT_INT'] = $dataComing["amt_transfer"];
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0066",
					":error_desc" => "ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0066",
				":error_desc" => "ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e)."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>