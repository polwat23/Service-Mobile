<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_smsmenu'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'sms',$conmysql)){
		$unuseTopic = $conmysql->prepare("UPDATE smsmenu SET sms_menu_status = '-9' WHERE id_smsmenu = :id_smsmenu");
		if($unuseTopic->execute([
			':id_smsmenu' => $dataComing["id_smsmenu"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "update";
			$arrayResult['RESPONSE'] = "Cannot delete SMS topic";
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