<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic',$conmysql)){
		$unuseTopic = $conmysql->prepare("UPDATE coresubmenu SET menu_status = '-9' WHERE id_submenu = :id_submenu");
		if($unuseTopic->execute([
			':id_submenu' => $dataComing["id_submenu"]
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