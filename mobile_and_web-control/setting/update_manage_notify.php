<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','setting_status','setting_name'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageNotification')){
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
			$arrExecute = [
				':status' => $dataComing["setting_status"],
				':id_token' => $payload["id_token"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateSetting;
			$arrError["ERROR_CODE"] = 'WS1020';
			$lib->addLogtoTxt($arrError,'notify_error');
			$arrayResult['RESPONSE_CODE'] = "WS1020";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถแก้ไขสถานะแจ้งเตือนได้ กรุณาติดต่อสหกรณ์ #WS1020";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot change status notification please contact cooperative #WS1020";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>