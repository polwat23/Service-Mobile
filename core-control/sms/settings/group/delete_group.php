<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_group'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup')){
		$DeleteSmsGroup = $conmysql->prepare("DELETE FROM smsgroupmember WHERE id_groupmember = :id_group");
		if($DeleteSmsGroup->execute([
			':id_group' => $dataComing["id_group"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "delete";
			$arrayResult['RESPONSE'] = "Cannot Delete Group member";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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