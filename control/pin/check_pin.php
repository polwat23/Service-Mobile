<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["pin"]) && isset($dataComing["member_no"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$new_token = null;
	if(!$is_accessToken){
		$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,$lib,$dataComing["channel"]);
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
	$checkPin = $conmysql->prepare("SELECT id_account FROM mdbmemberaccount WHERE pin = :pin and member_no = :member_no");
	$checkPin->execute([
		':pin' => $dataComing["pin"],
		':member_no' => $dataComing["member_no"]
	]);
	if($checkPin->rowCount() > 0){
		$arrayResult['RESULT'] = TRUE;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
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
?>