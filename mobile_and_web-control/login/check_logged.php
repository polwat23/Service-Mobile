<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$checkUserlogin = $conmysql->prepare("SELECT id_userlogin,is_login FROM gcuserlogin WHERE id_token = :id_token and is_login <> '0'
											and member_no = :member_no and unique_id = :unique_id");
	$checkUserlogin->execute([
		':id_token' => $payload["id_token"],
		':member_no' => $payload["member_no"],
		':unique_id' => $dataComing["unique_id"]
	]);
	if($checkUserlogin->rowCount() > 0){
		$rowLog = $checkUserlogin->fetch(PDO::FETCH_ASSOC);
		if($rowLog["is_login"] == '1'){
			$lib->addLogtoTxt([
				"access_date" => date('Y-m-d H:i:s'), 
				"member_no" => $payload["member_no"], 
				"access_token" => $access_token,
				"ip_address" => $dataComing["ip_address"] ?? 'unknown',
				"id_userlogin" => $rowLog["id_userlogin"]
			],'user_access_after_login');
			$arrayResult['RESULT'] = TRUE;
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0010";
			if($rowLog["is_login"] == '-9' || $rowLog["is_login"] == '-10') {
				$func->revoke_alltoken($payload["id_token"],'-9',true);
			}else if($rowLog["is_login"] == '-8' || $rowLog["is_login"] == '-99'){
				$func->revoke_alltoken($payload["id_token"],'-8',true);
			}else if($rowLog["is_login"] == '-7'){
				$func->revoke_alltoken($payload["id_token"],'-7',true);
			}else if($rowLog["is_login"] == '-5'){
				$func->revoke_alltoken($payload["id_token"],'-6',true);
			}
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0]['LOGOUT'.$rowLog["is_login"]][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
		}
		echo json_encode($arrayResult);
		exit();
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0009";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
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