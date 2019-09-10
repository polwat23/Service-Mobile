<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$id_token = null;
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
			$id_token = $is_refreshToken_arr["ID_TOKEN"];
		}
	}else{
		$id_token = $is_accessToken;
	}
	if($func->logout($id_token,'0',$conmysql)){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "SQL500";
		$arrayResult['RESPONSE'] = "Cannot logout !!";
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