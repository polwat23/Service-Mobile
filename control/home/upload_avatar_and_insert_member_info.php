<?php
require_once('../../autoload.php');

if(isset($dataComing["member_no"]) && isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["refresh_token"])
&& isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["encode_avatar"])){
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
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'MemberInfo')){
		$arrayResult = array();
		$member_no = $dataComing["member_no"];
		$encode_avatar = $dataComing["encode_avatar"];
		$destination = __DIR__.'/../../resource/avatar/'.$member_no;
		$extension = $lib->base64_to_img($encode_avatar,$destination);
		if($extension){
			$path_avatar = '/resource/avatar/'.$member_no.'.'.$extension.'?v='.$lib->randomText('all',2);
			$insertIntoInfo = $conmysql->prepare("UPDATE mdbmemberaccount SET path_avatar2 = :path_avatar,update_date = NOW() WHERE member_no = :member_no");
			if($insertIntoInfo->execute([
				':path_avatar' => $path_avatar,
				':member_no' => $member_no
			])){
				$arrayResult['PATH_AVATAR'] = $path_avatar;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Cannot update avatar path";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "UPLOAD500";
			$arrayResult['RESPONSE'] = "Extension is invalid";
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
?>