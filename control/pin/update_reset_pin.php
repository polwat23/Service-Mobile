<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset(isset($dataComing["unique_id"]) && isset($payload["member_no"]) && isset($dataComing["refresh_token"])){
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
		$conmysql->beginTransaction();
		$updateResetPin = $conmysql->prepare("UPDATE mdbmemberaccount SET pin = null,update_date = NOW() WHERE member_no = :member_no");
		if($updateResetPin->execute([
			':member_no' => $payload["member_no"]
		])){
			if($func->logoutAll(null,$payload["member_no"],'-10',$conmysql)){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Cannot reset PIN !!";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "SQL500";
			$arrayResult['RESPONSE'] = "Cannot reset PIN !!";
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