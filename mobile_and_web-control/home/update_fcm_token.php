<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['fcm_token'],$dataComing)){
	$updateNewToken = $conmysql->prepare("UPDATE gcmemberaccount SET fcm_token = :fcm_token WHERE member_no = :member_no");
	$updateNewToken->execute([
		':fcm_token' => $dataComing["fcm_token"],
		':member_no' => $payload["member_no"]
	]);
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
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