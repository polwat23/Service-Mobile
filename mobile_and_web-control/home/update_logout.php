<?php
require_once('../autoload.php');

if($func->logout($payload["id_token"],'0')){
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESPONSE_CODE'] = "WS1007";
	$arrayResult['RESPONSE_MESSAGE'] = "Cannot logout !!";
	$arrayResult['RESULT'] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>