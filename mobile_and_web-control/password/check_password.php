<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset($dataComing["unique_id"]) && isset($dataComing["password"]) && 
	isset($payload["member_no"]) && isset($dataComing["refresh_token"])){
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
		$getOldPassword = $conmysql->prepare("SELECT password,temppass,account_status FROM mdbmemberaccount 
												WHERE member_no = :member_no");
		$getOldPassword->execute([':member_no' => $payload["member_no"]]);
		if($getOldPassword->rowCount() > 0){
			$rowAccount = $getOldPassword->fetch();
			if($rowAccount['account_status'] == '-9'){
				if($dataComing["password"] == $rowAccount["temppass"]){
					$validpassword = true;
				}else{
					$validpassword = false;
				}
			}else{
				$validpassword = password_verify($dataComing["password"], $rowAccount['password']);
			}
			if($validpassword){
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL400";
				$arrayResult['RESPONSE'] = "Password was wrong";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "SQL400";
			$arrayResult['RESPONSE'] = "Do not have a membership";
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