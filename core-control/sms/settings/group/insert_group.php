<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','group_name','group_member'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup',$conmysql)){
		$insertSmsGroup = $conmysql->prepare("INSERT INTO smsgroupmember(group_name,group_member)
												VALUES(:group_name,:group_member)");
		if($insertSmsGroup->execute([
			':group_name' => $dataComing["group_name"],
			':group_member'=> $dataComing["group_member"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "insert";
			$arrayResult['RESPONSE'] = "Cannot insert Group member";
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