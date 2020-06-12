<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','account_receive','prinbal_clr','int_clr','contract_clr','request_amt','loantype_code','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$structureReqLoanPayment = array();
			$structureReqLoanPayment["coop_id"] = $config["COOP_ID"];
			$structureReqLoanPayment["loantype_code"] = $dataComing["loantype_code"];
			$structureReqLoanPayment["colltype_code"] = '01';
			$structureReqLoanPayment["member_no"] = $member_no;
			$structureReqLoanPayment["operate_date"] = date("c");
			$structureReqLoanPayment["contcredit_flag"] = '1';
			$structureReqLoanPayment["loancontract_no"] = 'AUTO';
			$structureReqLoanPayment["entry_id"] = 'mobile';
			$structureReqLoanPayment["loanpermiss_amt"] = $dataComing["loanpermit_amt"];
			$structureReqLoanPayment["loanrequest_amt"] = $dataComing["request_amt"];
			$structureReqLoanPayment["account_id"] = $dataComing["account_receive"];
			$structureReqLoanPayment["approve_amt"] = $dataComing["salary_amt"];
			$structureReqLoanPayment["fee_amt"] = $dataComing["fee_amt"];
			$structureReqLoanPayment["maxreceive_amt"] = $dataComing["loanpermit_amt"];
			$structureReqLoanPayment["contclr_no"] = $dataComing["contract_clr"];
			$structureReqLoanPayment["prinbal_clr"] = $dataComing["prinbal_clr"];
			$structureReqLoanPayment["intpayment_clr"] = $dataComing["int_clr"];
			$structureReqLoanPayment["item_amt"] = $dataComing["prinbal_clr"] + $dataComing["int_clr"];
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"atr_lnatm" => $structureReqLoanPayment
				];
				$resultWS = $clientWS->__call("of_saveloanmobile_atm_ivr", array($argumentWS));
				//$responseSoap = $resultWS->astr_lncalperiod;
				$arrayResult['PERIOD_PAYMENT'] = $resultWS;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0063",
					":error_desc" => "ไม่สามารถคำนวณสิทธิ์กู้ได้ "."\n".json_encode($e),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." ไม่สามารถคำนวณสิทธิ์กู้ได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".json_encode($e);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS0063";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0063",
				":error_desc" => "ไม่สามารถคำนวณสิทธิ์กู้ได้ "."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไม่สามารถคำนวณสิทธิ์กู้ได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".json_encode($e);
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