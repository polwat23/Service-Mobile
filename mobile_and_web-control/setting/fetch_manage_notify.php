<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageNotification')){
		$fetchSettingNotify = $conmysql->prepare("SELECT receive_notify_news,receive_notify_transaction,receive_login_email
													FROM gcuserlogin WHERE id_token = :id_token and is_login = '1'");
		$fetchSettingNotify->execute([':id_token' => $payload["id_token"]]);
		if($fetchSettingNotify->rowCount() > 0){
			$rowSetting = $fetchSettingNotify->fetch();
			$arrayResult["RECEIVE_NOTIFY_NEWS"] = $rowSetting["receive_notify_news"];
			$arrayResult["RECEIVE_NOTIFY_TRANSACTION"] = $rowSetting["receive_notify_transaction"];
			$arrayResult["RECEIVE_LOGIN_EMAIL"] = $rowSetting["receive_login_email"];
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
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