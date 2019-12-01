<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','allow_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'ManagementAccount')){
		$updateAccountBeenAllow = $conmysql->prepare("UPDATE gcuserallowacctransaction SET is_use = :allow_status WHERE deptaccount_no = :deptaccount_no");
		$updateAccountBeenbind = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = :allow_status WHERE deptaccount_no_coop = :deptaccount_no");
		if($updateAccountBeenAllow->execute([
			':allow_status' => $dataComing["allow_status"],
			':deptaccount_no' => $dataComing["deptaccount_no"],
		]) && $updateAccountBeenbind->execute([
			':allow_status' => $dataComing["allow_status"],
			':deptaccount_no' => $dataComing["deptaccount_no"],
		])){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "WS1025";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot update account allow status";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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