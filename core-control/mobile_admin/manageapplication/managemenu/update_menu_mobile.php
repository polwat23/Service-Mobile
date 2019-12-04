<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_status','id_menu'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$updatemenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = :menu_status
									 WHERE id_menu = :id_menu");
		if($updatemenu->execute([
			':menu_status' => $dataComing["menu_status"],
			':id_menu' => $dataComing["id_menu"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult["RESULT"] = FALSE;
		}
		echo json_encode($arrayResult);	
		}
		$arrayResult["MENU_MOBILE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
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