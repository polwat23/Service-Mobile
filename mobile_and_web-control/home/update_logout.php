<?php
require_once('../autoload.php');

if($func->logout($payload["id_token"],'0')){
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrError = array();
	$arrError["PAYLOAD"] = $payload;
	$arrError["ERROR_CODE"] = 'WS1007';
	$lib->addLogtoTxt($arrError,'logout_error');
	$arrayResult['RESPONSE_CODE'] = "WS1007";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>