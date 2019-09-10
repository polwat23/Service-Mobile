<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["id_memo"])
&& isset($dataComing["menu_component"]) && isset($dataComing["refresh_token"])){
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
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'DepositStatement')){
		$getMemo = $conmysql->prepare("SELECT memo_text,memo_icon_path FROM mdbmemodept WHERE id_memo = :id_memo");
		$getMemo->execute([':id_memo' => $dataComing["id_memo"]]);
		$rowMemo = $getMemo->fetch();
		$arrayResult['MEMO_TEXT'] = $rowMemo["memo_text"];
		$arrayResult['MEMO_ICON_PATH'] = $rowMemo["memo_icon_path"];
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