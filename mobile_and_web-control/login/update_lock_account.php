<?php
require_once('../autoload.php');

if(isset($dataComing["member_no"]) && isset($dataComing["api_key"]) && isset($dataComing["unique_id"])){
	$conmysql_nottest = $con->connecttomysql();
	if($api->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
		$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-8' WHERE member_no = :member_no");
		if($updateAccountStatus->execute([':member_no' => $dataComing["member_no"]])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "SQL500";
			$arrayResult['RESPONSE'] = "Cannot lock account";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult = array();
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>