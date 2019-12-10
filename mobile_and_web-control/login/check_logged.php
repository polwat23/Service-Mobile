<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$checkMemberResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
	$checkMemberResign->execute([':member_no' => $payload["member_no"]]);
	$rowMemberResign = $checkMemberResign->fetch();
	if(isset($rowMemberResign["RESIGN_STATUS"]) && $rowMemberResign["RESIGN_STATUS"] == 0){
		$checkUserlogin = $conmysql->prepare("SELECT id_userlogin,is_login FROM gcuserlogin WHERE id_token = :id_token and is_login <> '0'
												and member_no = :member_no and unique_id = :unique_id");
		$checkUserlogin->execute([
			':id_token' => $payload["id_token"],
			':member_no' => $payload["member_no"],
			':unique_id' => $dataComing["unique_id"]
		]);
		if($checkUserlogin->rowCount() > 0){
			$rowLog = $checkUserlogin->fetch();
			if($rowLog["is_login"] == '1'){
				try{
					$logAccess = [
						"access_date" => date('Y-m-d H:i:s'), 
						"member_no" => $payload["member_no"], 
						"access_token" => $access_token,
						"ip_address" => $dataComing["ip_address"] ?? 'unknown',
						"id_userlogin" => $rowLog["id_userlogin"]
					];
					$conmongo->GCLOGUSERACCESSAFTERLOGIN->insertOne($logAccess);
				}catch(Exception $e) {
					$lib->addLogtoTxt([
						"access_date" => date('Y-m-d H:i:s'), 
						"member_no" => $payload["member_no"], 
						"access_token" => $access_token,
						"ip_address" => $dataComing["ip_address"] ?? 'unknown',
						"id_userlogin" => $rowLog["id_userlogin"]
					],'GCLOGUSERACCESSAFTERLOGIN');
				}
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
			}else{
				if($rowLog["is_login"] == '-9' || $rowLog["is_login"] == '-10') {
					$func->revoke_alltoken($payload["id_token"],'-9',true);
				}else if($rowLog["is_login"] == '-8' || $rowLog["is_login"] == '-99'){
					$func->revoke_alltoken($payload["id_token"],'-8',true);
				}else if($rowLog["is_login"] == '-7'){
					$func->revoke_alltoken($payload["id_token"],'-7',true);
				}
				$arrayResult["RESPONSE_MESSAGE"] = $config['LOGOUT'.$rowLog["is_login"]];
				$arrayResult['RESULT'] = FALSE;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0009";
			$arrayResult['RESPONSE_MESSAGE'] = "You cannot access please login";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0021";
		$arrayResult['RESPONSE_MESSAGE'] = "ระบบไม่รองรับสมาชิกเกษียณ";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		http_response_code(403);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>