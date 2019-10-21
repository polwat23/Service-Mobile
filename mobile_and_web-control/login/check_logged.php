<?php
require_once('../autoload.php');

$status_token = $api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"]);
if($status_token){
	if(isset($dataComing["unique_id"]) && isset($dataComing["channel"]) && 
	isset($payload["member_no"]) && isset($dataComing["refresh_token"]) && isset($payload["id_token"])){
		$new_token = null;
		$id_token = $payload["id_token"];
		if($status_token == 'expired'){
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
		$checkUserlogin = $conmysql->prepare("SELECT id_userlogin,is_login FROM gcuserlogin WHERE id_token = :id_token and is_login <> '0' 
											and member_no = :member_no and unique_id = :unique_id");
		$checkUserlogin->execute([
			':id_token' => $id_token,
			':member_no' => $payload["member_no"],
			':unique_id' => $dataComing["unique_id"]
		]);
		if($checkUserlogin->rowCount() > 0){
			$rowLog = $checkUserlogin->fetch();
			if($rowLog["is_login"] == '1'){
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
			}else{
				$arrayResult['RESULT'] = TRUE;
				$arrayResult["MESSAGE_LOGOUT"] = $config['LOGOUT'.$rowLog["is_login"]];
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
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