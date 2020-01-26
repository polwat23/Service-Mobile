<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','setting_status','setting_name'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageNotification')){
		$updateSetting = $conmysql->prepare("UPDATE gcuserlogin SET ".strtolower($dataComing["setting_name"])." = :status
												WHERE id_token = :id_token and is_login = '1'");
		if($updateSetting->execute([
			':status' => $dataComing["setting_status"],
			':id_token' => $payload["id_token"]
		])){
			$arrayResult['RESULT'] = TRUE;
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
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>