<?php
require_once('../autoload.php');

$updateResetPin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = null WHERE member_no = :member_no");
if($updateResetPin->execute([
	':member_no' => $payload["member_no"]
])){
	if($func->logoutAll(null,$payload["member_no"],'-10')){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS1017";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrExecute = [
		':member_no' => $payload["member_no"]
	];
	$arrError = array();
	$arrError["EXECUTE"] = $arrExecute;
	$arrError["QUERY"] = $updateResetPin;
	$arrError["ERROR_CODE"] = 'WS1016';
	$lib->addLogtoTxt($arrError,'pin_error');
	$arrayResult['RESPONSE_CODE'] = "WS1016";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>