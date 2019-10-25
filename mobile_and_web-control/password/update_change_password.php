<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		if(isset($dataComing["password"])){
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
			if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'SettingChangePassword')){
				$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
				$conmysql->beginTransaction();
				$changePassword = $conmysql->prepare("UPDATE gcmemberaccount SET password = :password,temppass = null,account_status = '1'
														WHERE member_no = :member_no");
				if($changePassword->execute([
					':password' => $password,
					':member_no' => $payload["member_no"]
				])){
					if($func->logoutAll($id_token,$payload["member_no"],'-9',$conmysql)){
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