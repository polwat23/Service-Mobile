<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['id_token'],$payload)){
	if($func->logout($payload["id_token"],'0',$conmysql)){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS1007";
		$arrayResult['RESPONSE_MESSAGE'] = "Cannot logout !!";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>