<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		if(isset($dataComing["unique_id"]) && isset($payload["user_type"]) && isset($dataComing["menu_component"]) 
		&& isset($dataComing["refresh_token"]) && isset($dataComing['resolution']) && isset($payload["id_token"])){
			$new_token = null;
			$id_token = $payload["id_token"];
			if($status_token === 'expired'){
				$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
				$dataComing["channel"],$payload,$jwt_token,$config["SECRET_KEY_JWT"]);
				if(!$is_refreshToken_arr){
					$arrayResult['RESPONSE_CODE'] = "SQL409";
					$arrayResult['RESPONSE'] = "Invalid RefreshToken is not correct or RefreshToken was expired";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(203);
					echo json_encode($arrayResult);
					exit();
				}else{
					$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
				}
			}
			if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'SettingTheme')){
				$jsonTheme = json_decode(file_get_contents(__DIR__.'/../../json/theme.json'), true);
				if($dataComing['resolution'] >= 1440){
					$deviceResolution = 'qhd';
				}else if($dataComing['resolution'] >= 1080){
					$deviceResolution = 'fhd';
				}else if($dataComing['resolution'] >= 720){
					$deviceResolution = 'hd';
				}else{
					$deviceResolution = 'sd';
				}
				$responseData = [];
				$responseData['default'] = $jsonTheme['default'];
				$theme = [];
				foreach($jsonTheme['theme'] as $value){
				$getImageBySize = [];
				$getImageBySize['name'] = $value['name'];
				$getImageBySize['url'] = $value[$deviceResolution];
				array_push($theme, $getImageBySize);
				}
				$responseData['theme'] = $theme;
				echo json_encode($responseData);
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
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>