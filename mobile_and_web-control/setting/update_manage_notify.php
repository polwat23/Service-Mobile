<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','setting_status','setting_name'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'SettingManageNotification')){
		$updateSetting = $conmysql->prepare("UPDATE gcuserlogin SET ".strtolower($dataComing["setting_name"])." = :status
												WHERE id_token = :id_token and is_login = '1'");
		if($updateSetting->execute([
			':status' => $dataComing["setting_status"],
			':id_token' => $payload["id_token"]
		])){
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS1019";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot change this setting";
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