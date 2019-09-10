<?php
require_once('../../autoload.php');

$arrayResult = array();
if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["channel"]) && 
isset($dataComing["member_no"]) && isset($dataComing["refresh_token"])){
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
	$checkUserlogin = $conmysql->prepare("SELECT id_userlogin,is_login FROM mdbuserlogin WHERE id_token = :id_token and is_login <> '0' 
										and member_no = :member_no and unique_id = :unique_id");
	$checkUserlogin->execute([
		':id_token' => $id_token,
		':member_no' => $dataComing["member_no"],
		':unique_id' => $dataComing["unique_id"]
	]);
	if($checkUserlogin->rowCount() > 0){
		$rowLog = $checkUserlogin->fetch();
		if($rowLog["is_login"] == '1'){
			if($dataComing["member_no"] == 'dev@mode'){
				$insertAccess = $conmysql->prepare("INSERT INTO mdbuseraccessafterlogin(access_date,id_userlogin)
													VALUES(NOW(),:id_userlogin)");
				if($insertAccess->execute([':id_userlogin' => $rowLog["id_userlogin"]])){
					$arrayResult['RESULT'] = TRUE;
					if(isset($new_token)){
						$arrayResult['NEW_TOKEN'] = $new_token;
					}
				}else{
					$text = '#Access Error : '.date("Y-m-d H:i:s").' > ID_TOKEN : '.$id_token.' | Check logged before check PIN';
					file_put_contents(__DIR__.'/../../log/log_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "SQL500";
					$arrayResult['RESPONSE'] = "Cannot access !!";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(203);
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
			}
		}else{
			$arrayResult['RESULT'] = TRUE;
			$arrayResult["MESSAGE_LOGOUT"] = $config[$rowLog["is_login"]];
		}
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
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