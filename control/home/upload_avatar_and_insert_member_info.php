<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset($payload["member_no"]) && isset($dataComing["unique_id"]) && isset($dataComing["refresh_token"])
	&& isset($payload["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["encode_avatar"])){
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
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'MemberInfo')){
			$arrayResult = array();
			$member_no = $payload["member_no"];
			$encode_avatar = $dataComing["encode_avatar"];
			$destination = __DIR__.'/../../resource/avatar/'.$member_no;
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createAvatar = $lib->base64_to_img($encode_avatar,$file_name,$destination);
			if($createAvatar){
				$path_avatar = '/resource/avatar/'.$member_no.'/'.$createAvatar;
				$insertIntoInfo = $conmysql->prepare("UPDATE mdbmemberaccount SET path_avatar = :path_avatar,update_date = NOW() WHERE member_no = :member_no");
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
}else{
	$arrayResult['RESPONSE_CODE'] = "HEADER500";
	$arrayResult['RESPONSE'] = "Authorization token invalid";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>