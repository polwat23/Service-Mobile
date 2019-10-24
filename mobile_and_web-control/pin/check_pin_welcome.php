<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
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
		$checkPin = $conmysql->prepare("SELECT pin FROM gcmemberaccount WHERE member_no = :member_no");
		$checkPin->execute([
			':member_no' => $payload["member_no"]
		]);
		$rowPin = $checkPin->fetch();
		// Pin Status : 9 => DEV, 1 => TRUE, 0 => FALSE
		if(isset($rowPin["pin"])){
			if($payload["user_type"] == '9'){
				$arrayResult['RESULT'] = 9;
			}else{
				$arrayResult['RESULT'] = 1;
			}
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = 0;
			echo json_encode($arrayResult);
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "HEADER500";
		$arrayResult['RESPONSE'] = "Authorization token invalid";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}
?>