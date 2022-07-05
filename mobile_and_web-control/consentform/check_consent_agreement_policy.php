<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ConsentAgreement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getAccept = $conoracle->prepare("SELECT ID_ACCEPT FROM gcacceptpolicy WHERE member_no = :member_no and policy_id = 'v2'");
		$getAccept->execute([':member_no' => $payload["member_no"]]);
		$rowAccept = $getAccept->fetch(PDO::FETCH_ASSOC);
		if(empty($rowAccept["ID_ACCEPT"]) || $rowAccept["ID_ACCEPT"] == ""){
			$arrConsentForm = array();
			if(preg_replace('/\./','',$dataComing["app_version"]) >= '100'){
				$arrayResult['TERMS']["TERMS_URL"] = $config["URL_POLICY"];
				$arrayResult['TERMS']["TERMS_ID"] = 'v2';
			}
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
