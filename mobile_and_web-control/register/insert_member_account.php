<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','phone','password','api_token','unique_id','menu_component','channel','os_channel'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	if($func->check_permission(null,$dataComing["menu_component"],'AppRegister')){
		$member_no = strtolower(str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT));
		$email = isset($dataComing["email"]) && $dataComing["email"] != '' ? $dataComing["email"] : null;
		$phone = $dataComing["phone"];
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$insertAccount = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,register_channel,os_channel) 
											VALUES(:member_no,:password,:phone,:email,:channel,:os_channel)");
		if($insertAccount->execute([
			':member_no' => $member_no,
			':password' => $password,
			':phone' => $phone,
			':email' => $email,
			':channel' => $arrPayload["VALIDATE"]["channel"],
			':os_channel' => $dataComing["os_channel"]
		])){
			$arrayResult = array();
			$arrayResult['MEMBER_NO'] = $member_no;
			$arrayResult['PASSWORD'] = $dataComing["password"];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "WS1017";
			$arrayResult['RESPONSE_MESSAGE'] = "Insert member account !!";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
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