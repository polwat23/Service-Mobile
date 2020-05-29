<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','unique_id','api_token'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-8',counter_wrongpass = 0 WHERE member_no = :member_no");
	if($updateAccountStatus->execute([':member_no' => $dataComing["member_no"]])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrExecute = [
			':member_no' => $dataComing["member_no"]
		];
		$arrError = array();
		$arrError["EXECUTE"] = $arrExecute;
		$arrError["QUERY"] = $updateAccountStatus;
		$arrError["ERROR_CODE"] = 'WS1010';
		$lib->addLogtoTxt($arrError,'lock_error');
		$arrayResult['RESPONSE_CODE'] = "WS1010";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
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