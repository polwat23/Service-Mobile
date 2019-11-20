<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type'],$payload) && $lib->checkCompleteArgument(['member_no','email','phone','password','api_key','unique_id','menu_component'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'AppRegister')){
		$email = $dataComing["email"];
		$phone = $dataComing["phone"];
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$insertAccount = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email) 
											VALUES(:member_no,:password,:phone,:email)");
		if($insertAccount->execute([
			':member_no' => $dataComing["member_no"],
			':password' => $password,
			':phone' => $phone,
			':email' => $email
		])){
			$arrayResult = array();
			$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
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