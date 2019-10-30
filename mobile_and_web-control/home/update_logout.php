<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['id_token'],$payload)){
	if($func->logout($payload["id_token"],'0',$conmysql)){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "5005";
		$arrayResult['RESPONSE_AWARE'] = "update";
		$arrayResult['RESPONSE'] = "Cannot logout !!";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>