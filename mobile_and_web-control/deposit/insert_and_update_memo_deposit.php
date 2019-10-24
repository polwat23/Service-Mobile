<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		if(isset($dataComing["memo_text"])&& isset($dataComing["account_no"]) && isset($dataComing["seq_no"])
		&& isset($dataComing["memo_icon_path"])){
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
			if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'DepositStatement')){
				$account_no = preg_replace('/-/','',$dataComing["account_no"]);
				$updateMemoDept = $conmysql->prepare("UPDATE gcmemodept SET memo_text = :memo_text,memo_icon_path = :memo_icon_path
														WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
				if($updateMemoDept->execute([
					':memo_text' => $dataComing["memo_text"],
					':memo_icon_path' => $dataComing["memo_icon_path"],
					':deptaccount_no' => $account_no,
					':seq_no' => $dataComing["seq_no"]
				]) && $updateMemoDept->rowCount() > 0){
					if(isset($new_token)){
						$arrayResult['NEW_TOKEN'] = $new_token;
					}
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$insertMemoDept = $conmysql->prepare("INSERT INTO mdbmemodept(memo_text,memo_icon_path,deptaccount_no,seq_no) 
															VALUES(:memo_text,:memo_icon_path,:deptaccount_no,:seq_no)");
					if($insertMemoDept->execute([
						':memo_text' => $dataComing["memo_text"],
						':memo_icon_path' => $dataComing["memo_icon_path"],
						':deptaccount_no' => $account_no,
						':seq_no' => $dataComing["seq_no"]
					])){
						if(isset($new_token)){
							$arrayResult['NEW_TOKEN'] = $new_token;
						}
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE_CODE'] = "SQL500";
						$arrayResult['RESPONSE'] = "Insert Memo failed !!";
						$arrayResult['RESULT'] = FALSE;
						http_response_code(203);
						echo json_encode($arrayResult);
						exit();
					}
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