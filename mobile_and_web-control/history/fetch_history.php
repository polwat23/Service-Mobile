<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		if(isset($dataComing["type_history"])){
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
			if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'Notification')){
				$arrGroupHis = array();
				$executeData = [
					':member_no' => $payload["member_no"],
					':his_type' => $dataComing["type_history"]
				];
				$extraQuery = "";
				if(isset($dataComing["fetch_type"])){
					switch($dataComing["fetch_type"]){
						case "refresh":
							$executeData[':id_history'] = isset($dataComing["id_history"]) ? $dataComing["id_history"] : 16777215; // max number int(12) of id_history
							$extraQuery = "and id_history > :id_history";
							break;
						case "more":
							$executeData[':id_history'] = isset($dataComing["id_history"]) ? $dataComing["id_history"] : 0;
							$extraQuery = "and id_history < :id_history";
							break;
					}
				}
				$getHistory = $conmysql->prepare("SELECT id_history,his_title,his_detail,receive_date,his_read_status FROM gchistory 
													WHERE member_no = :member_no and his_type = :his_type $extraQuery ORDER BY id_history DESC LIMIT 10");
				$getHistory->execute($executeData);
				while($rowHistory = $getHistory->fetch()){
					$arrHistory = array();
					$arrHistory["TITLE"] = $rowHistory["his_title"];
					$arrHistory["DETAIL"] = $rowHistory["his_detail"];
					$arrHistory["READ_STATUS"] = $rowHistory["his_read_status"];
					$arrHistory["ID_HISTORY"] = $rowHistory["id_history"];
					$arrHistory["RECEIVE_DATE"] = $lib->convertdate($rowHistory["receive_date"],'D m Y',true);
					$arrGroupHis[] = $arrHistory;
				}
				$arrayResult['HISTORY'] = $arrGroupHis;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
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
