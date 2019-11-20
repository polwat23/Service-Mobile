<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','unique_id','api_token'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-8' WHERE member_no = :member_no");
	if($updateAccountStatus->execute([':member_no' => $dataComing["member_no"]])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "WS1010";
		$arrayResult['RESPONSE_MESSAGE'] = "Cannot lock account";
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