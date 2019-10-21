<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset($dataComing["unique_id"]) && isset($payload["user_type"]) && isset($dataComing["menu_component"]) 
	&& isset($dataComing["refresh_token"])){
		$is_accessToken = $api->check_accesstoken($access_token,$conmysql);
		$id_token = null;
		$new_token = null;
		if(!$is_accessToken){
			$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$lib,$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "SQL409";
				$arrayResult['RESPONSE'] = "Invalid Access Maybe AccessToken and RefreshToken is not correct";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}else{
				$id_token = $is_refreshToken_arr["ID_TOKEN"];
				$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
			}
		}else{
			$id_token = $is_accessToken;
		}
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'SettingManageNotification')){
			$fetchSettingNotify = $conmysql->prepare("SELECT receive_notify_news,receive_notify_transaction,receive_login_email,is_sound_notify
														FROM mdbuserlogin WHERE id_token = :id_token and is_login = '1'");
			$fetchSettingNotify->execute([':id_token' => $id_token]);
			if($fetchSettingNotify->rowCount() > 0){
				$rowSetting = $fetchSettingNotify->fetch();
				$arrayResult["RECEIVE_NOTIFY_NEWS"] = $rowSetting["receive_notify_news"];
				$arrayResult["RECEIVE_NOTIFY_TRANSACTION"] = $rowSetting["receive_notify_transaction"];
				$arrayResult["RECEIVE_LOGIN_EMAIL"] = $rowSetting["receive_login_email"];
				$arrayResult["IS_SOUND_NOTIFY"] = $rowSetting["is_sound_notify"];
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL400";
				$arrayResult['RESPONSE'] = "You are logout !!";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "PARAM500";
			$arrayResult['RESPONSE'] = "Not permission this menu";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "PARAM400";
		$arrayResult['RESPONSE'] = "Not complete parameter";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "HEADER500";
	$arrayResult['RESPONSE'] = "Authorization token invalid";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>