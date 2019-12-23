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
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถออกจากระบบได้ในขณะนี้ #WS1007";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Cannot logout this moment #WS1007";
	}
	$arrayResult['RESULT'] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>