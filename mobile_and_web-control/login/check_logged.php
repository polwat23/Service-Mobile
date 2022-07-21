<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$checkUserlogin = $conoracle->prepare("SELECT id_userlogin,is_login FROM gcuserlogin WHERE id_token = :id_token and is_login <> '0'
											and member_no = :member_no and unique_id = :unique_id");
	$checkUserlogin->execute([
		':id_token' => $payload["id_token"],
		':member_no' => $payload["member_no"],
		':unique_id' => $dataComing["unique_id"]
	]);
	$rowLog = $checkUserlogin->fetch(PDO::FETCH_ASSOC);
	if(isset($rowLog["ID_USERLOGIN"]) && $rowLog["ID_USERLOGIN"] != ""){
		if($rowLog["IS_LOGIN"] == '1'){
			$lib->addLogtoTxt([
				"access_date" => date('Y-m-d H:i:s'), 
				"member_no" => $payload["member_no"], 
				"access_token" => $access_token,
				"ip_address" => $dataComing["ip_address"] ?? 'unknown',
				"id_userlogin" => $rowLog["ID_USERLOGIN"]
			],'user_access_after_login');
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0010";
			if($rowLog["IS_LOGIN"] == '-9' || $rowLog["IS_LOGIN"] == '-10') {
				$func->revoke_alltoken($payload["id_token"],'-9',true);
			}else if($rowLog["IS_LOGIN"] == '-8' || $rowLog["IS_LOGIN"] == '-99'){
				$func->revoke_alltoken($payload["id_token"],'-8',true);
			}else if($rowLog["IS_LOGIN"] == '-7'){
				$func->revoke_alltoken($payload["id_token"],'-7',true);
			}else if($rowLog["IS_LOGIN"] == '-5'){
				$func->revoke_alltoken($payload["id_token"],'-6',true);
			}
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0]['LOGOUT'.$rowLog["IS_LOGIN"]][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
		}
		require_once('../../include/exit_footer.php');
		
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0009";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>