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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>