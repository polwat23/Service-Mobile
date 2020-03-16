<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkPin = $conmysql->prepare("SELECT member_no,pin FROM gcmemberaccount WHERE member_no = :member_no");
	$checkPin->execute([
		':member_no' => $payload["member_no"]
	]);
	$rowaccount = $checkPin->fetch(PDO::FETCH_ASSOC);
	if(password_verify($dataComing["pin"], $rowaccount['pin']) || (isset($dataComing["flag"]) && $dataComing["flag"] == "TOUCH_ID")){
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
	}else{
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
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