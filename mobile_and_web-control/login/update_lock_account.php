<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','api_key','unique_id'],$dataComing) && $anonymous){
	$conmysql_nottest = $con->connecttomysql();
	if($api->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
		$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-8' WHERE member_no = :member_no");
		if($updateAccountStatus->execute([':member_no' => $dataComing["member_no"]])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "update";
			$arrayResult['RESPONSE'] = "Cannot lock account";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "4007";
		$arrayResult['RESPONSE_AWARE'] = "api";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(407);
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