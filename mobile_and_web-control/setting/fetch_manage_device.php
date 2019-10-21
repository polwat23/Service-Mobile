<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset($dataComing["unique_id"]) && isset($payload["member_no"])
	&& isset($payload["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
		$is_accessToken = $api->check_accesstoken($access_token,$conmysql);
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
				$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
			}
		}
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'SettingManageDevice')){
			$arrGroupDevice = array();
			$fetchSettingDevice = $conmysql->prepare("SELECT ml.device_name,ml.os_platform,ml.channel,ml.unique_id,ml.login_date,MAX(af.access_date) access_date,ml.id_token
														FROM mdbuserlogin ml LEFT JOIN mdbuseraccessafterlogin af ON ml.id_userlogin = af.id_userlogin
														WHERE ml.is_login = '1' and ml.member_no = :member_no GROUP BY unique_id");
			$fetchSettingDevice->execute([':member_no' => $payload["member_no"]]);
			while($rowSetting = $fetchSettingDevice->fetch()){
				$arrDevice = array();
				$arrDevice["DEVICE_NAME"] = $rowSetting["device_name"];
				$arrDevice["OS_PLATFORM"] = $rowSetting["os_platform"];
				$arrDevice["CHANNEL"] = $rowSetting["channel"];
				if($rowSetting["unique_id"] == $dataComing["unique_id"]){
					$arrDevice["THIS_DEVICE"] = true;
				}
				$arrDevice["LOGIN_DATE"] = isset($rowSetting["login_date"]) ? $lib->convertdate($rowSetting["login_date"],'D m Y',true) : null;
				$arrDevice["ACCESS_DATE"] = isset($rowSetting["access_date"]) ? $lib->convertdate($rowSetting["access_date"],'D m Y',true) : null;
				$arrDevice["ID_TOKEN"] = $rowSetting["id_token"];
				$arrGroupDevice[] = $arrDevice;
			}
			$arrayResult["DEVICE"] = $arrGroupDevice;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
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