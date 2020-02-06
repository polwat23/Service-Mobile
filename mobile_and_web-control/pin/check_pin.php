<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkPin = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE pin = :pin and member_no = :member_no");
	$checkPin->execute([
		':pin' => $dataComing["pin"],
		':member_no' => $payload["member_no"]
	]);
	if($checkPin->rowCount() > 0){
		$rowaccount = $checkPin->fetch();
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>