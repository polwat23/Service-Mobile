<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["member_no"]) && isset($dataComing["type_history"])
&& isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
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
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'Notification')){
		$arrGroupHis = array();
		$data_limit = isset($dataComing["limit"]) ? (is_numeric($dataComing["limit"]) ? $dataComing["limit"] : 10) : 10;
		$getHistory = $conmysql->prepare("SELECT id_history,his_title,his_detail,receive_date,his_read_status FROM mdbhistory 
											WHERE member_no = :member_no and his_type = :his_type and id_history > :id_history ORDER BY id_history DESC LIMIT {$data_limit}");
		$getHistory->execute([
			':member_no' => $dataComing["member_no"],
			':his_type' => $dataComing["type_history"],
			':id_history' => isset($dataComing["id_history"]) ? $dataComing["id_history"] : 0
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
?>