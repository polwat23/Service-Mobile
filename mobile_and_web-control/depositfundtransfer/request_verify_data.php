<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','deptaccount_no','bank_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrDataAPI["ToCoopAccountNo"] = $dataComing["deptaccount_no"];
		$arrDataAPI["FromBankAccountNo"] = $dataComing["bank_account_no"];
		$arrDataAPI["DepositAmount"] = $dataComing["amt_transfer"];
		$arrDataAPI["UserRequestDate"] = date('c');
		$arrDataAPI["Note"] = "Check Fee of VerifyData in Deposit";
		$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/CheckDepositFee",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckDepositFee",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckDepositFee";
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			$arrayResult['FEE_AMT'] = 0;
			$arrayResult['BANK_ACCOUNT_ENC'] = $dataComing["bank_account_no"];
			$arrayResult['ACCOUNT_NAME'] = $arrResponseAPI->toCOOPAccountName;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0028";
			if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
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