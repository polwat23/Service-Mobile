<?php
require_once('../../autoload.php');

if($api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"])){
	if(isset($dataComing["unique_id"]) && isset($payload["member_no"]) && isset($dataComing["type_history"])
	&& isset($payload["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
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
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'Notification')){
			$arrGroupHis = array();
			$getHistory = $conmysql->prepare("SELECT id_history,his_title,his_detail,receive_date,his_read_status FROM mdbhistory 
												WHERE member_no = :member_no and his_type = :his_type and id_history < :id_history ORDER BY id_history DESC LIMIT 10");
			$getHistory->execute([
				':member_no' => $payload["member_no"],
				':his_type' => $dataComing["type_history"],
				':id_history' => isset($dataComing["id_history"]) ? $dataComing["id_history"] : 999999999999 // max number int(12) of id_history
			]);
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
?>