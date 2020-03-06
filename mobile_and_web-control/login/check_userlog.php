<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
	$checkResign->execute([':member_no' => $payload["member_no"]]);
	$rowResign = $checkResign->fetch(PDO::FETCH_ASSOC);
	if($rowResign["RESIGN_STATUS"] == '1'){
		$arrayResult['RESPONSE_CODE'] = "WS0051";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	if(isset($dataComing["flag"]) && $dataComing["flag"] == "TOUCH_ID"){
		$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
		$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "WS0014";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(401);
			echo json_encode($arrayResult);
			exit();
		}
		$arrayResult['NEW_TOKEN'] = $is_refreshToken_arr["ACCESS_TOKEN"];
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
		exit();
	}
	$checkPinNull = $conmysql->prepare("SELECT pin,account_status FROM gcmemberaccount WHERE member_no = :member_no and account_status IN('1','-9')");
	$checkPinNull->execute([':member_no' => $payload["member_no"]]);
	$rowPinNull = $checkPinNull->fetch(PDO::FETCH_ASSOC);
	if(isset($rowPinNull["pin"])){
		if(password_verify($dataComing["pin"], $rowPinNull['pin'])){
			if($rowPinNull["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "WS0014";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(401);
				echo json_encode($arrayResult);
				exit();
			}
			$arrayResult['NEW_TOKEN'] = $is_refreshToken_arr["ACCESS_TOKEN"];
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':ip_address' => $dataComing["ip_address"]
			];
			$log->writeLog('use_application',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0011";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$pin = password_hash($dataComing["pin"], PASSWORD_DEFAULT);
		$updatePin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin WHERE member_no = :member_no");
		if($updatePin->execute([
			':pin' => $pin,
			':member_no' => $payload["member_no"]
		])){
			if($rowPinNull["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "WS0014";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(401);
				echo json_encode($arrayResult);
				exit();
			}
			$arrayResult['NEW_TOKEN'] = $is_refreshToken_arr["ACCESS_TOKEN"];
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':ip_address' => $dataComing["ip_address"]
			];
			$log->writeLog('use_application',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':pin' => $dataComing["pin"],
				':member_no' => $payload["member_no"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updatePin;
			$arrError["ERROR_CODE"] = 'WS1009';
			$lib->addLogtoTxt($arrError,'pin_error');
			$arrayResult['RESPONSE_CODE'] = "WS1009";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>