<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','int_rate','period','request_amt','loantype_code','salary_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$structureReqLoanPayment = array();
			$structureReqLoanPayment["calperiod_intrate"] = $dataComing["int_rate"];
			$structureReqLoanPayment["calperiod_maxinstallment"] = $dataComing["period"];
			$structureReqLoanPayment["calperiod_prnamt"] = $dataComing["request_amt"];
			$structureReqLoanPayment["loanpayment_type"] = 2;
			$structureReqLoanPayment["loantype_code"] = $dataComing["loantype_code"];
			$structureReqLoanPayment["period_installment"] = $dataComing["period"];
			$structureReqLoanPayment["period_payment"] = 0;
			$structureReqLoanPayment["progess_flag"] = 0;
			$structureReqLoanPayment["progess_rate"] = 0;
			$structureReqLoanPayment["salary_amount"] = $dataComing["salary_amt"];
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lncalperiod" => $structureReqLoanPayment
				];
				$resultWS = $clientWS->__call("of_calperiodpay", array($argumentWS));
				$responseSoap = $resultWS->astr_lncalperiod;
				$arrayResult['PERIOD_PAYMENT'] = $responseSoap->period_payment ?? 0;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0062",
					":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n".json_encode($e),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".json_encode($e);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS0062";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0062",
				":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".json_encode($e);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS0062";
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