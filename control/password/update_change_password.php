<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["password"]) && 
isset($dataComing["member_no"]) && isset($dataComing["refresh_token"]) && isset($dataComing["user_type"]) && isset($dataComing["menu_component"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$id_token = null;
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
			$id_token = $is_refreshToken_arr["ID_TOKEN"];
			$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
		}
	}else{
		$id_token = $is_accessToken;
	}
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'SettingChangePassword')){
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$conmysql->beginTransaction();
		$changePassword = $conmysql->prepare("UPDATE mdbmemberaccount SET password = :password,temppass = null,account_status = '1',update_date = NOW()
												WHERE member_no = :member_no");
		if($changePassword->execute([
			':password' => $password,
			':member_no' => $dataComing["member_no"]
		])){
			if($func->logoutAll($id_token,$dataComing["member_no"],'-9',$conmysql)){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Cannot change password";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "SQL500";
			$arrayResult['RESPONSE'] = "Cannot change password";
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