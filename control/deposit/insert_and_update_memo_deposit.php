<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["memo_text"])
&& isset($dataComing["menu_component"]) && isset($dataComing["account_no"]) && isset($dataComing["seq_no"])
&& isset($dataComing["memo_icon_path"]) && isset($dataComing["refresh_token"])){
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
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$updateMemoDept = $conmysql->prepare("UPDATE mdbmemodept SET memo_text = :memo_text,memo_icon_path = :memo_icon_path,update_date = NOW()
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
?>