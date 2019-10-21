<?php
require_once('../autoload.php');

$status_token = $api->validate_jwttoken($author_token,$jwt_token,$config["SECRET_KEY_JWT"]);
if($status_token){
	if(isset($dataComing["unique_id"]) && isset($dataComing["channel"]) && 
	isset($dataComing["pin"]) && isset($payload["member_no"]) && 
	isset($payload["id_userlogin"]) && isset($dataComing["refresh_token"]) && isset($payload["id_token"])){
		$new_token = null;
		$id_token = $payload["id_token"];
		if($status_token == 'expired'){
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
		$checkPinNull = $conmysql->prepare("SELECT pin FROM gcmemberaccount WHERE member_no = :member_no and account_status NOT IN('-6','-7','-8')");
		$checkPinNull->execute([':member_no' => $payload["member_no"]]);
		$rowPinNull = $checkPinNull->fetch();
		if(isset($rowPinNull["pin"])){
			$checkPin = $conmysql->prepare("SELECT id_account,account_status FROM gcmemberaccount WHERE member_no = :member_no and pin = :pin");
			$checkPin->execute([
				':member_no' => $payload["member_no"],
				':pin' => $dataComing["pin"]
			]);
			if($checkPin->rowCount() > 0){
				$rowaccount = $checkPin->fetch();
				if($rowaccount["account_status"] == '-9'){
					$arrayResult['TEMP_PASSWORD'] = TRUE;
				}else{
					$arrayResult['TEMP_PASSWORD'] = FALSE;
				}
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL403";
				$arrayResult['RESPONSE'] = "Invalid Pin";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$updatePin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin WHERE member_no = :member_no");
			if($updatePin->execute([
				':pin' => $dataComing["pin"],
				':member_no' => $payload["member_no"]
			])){
				$fetchAcc = $conmysql->prepare("SELECT account_status FROM gcmemberaccount WHERE member_no = :member_no");
				$fetchAcc->execute([
					':member_no' => $payload["member_no"]
				]);
				$rowaccount = $fetchAcc->fetch();
				if($rowaccount["account_status"] == '-9'){
					$arrayResult['TEMP_PASSWORD'] = TRUE;
				}else{
					$arrayResult['TEMP_PASSWORD'] = FALSE;
				}
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Update Pin Failed";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
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