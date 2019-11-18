<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type'],$payload) && $lib->checkCompleteArgument(['member_no','email','phone','password','api_key','unique_id','menu_component'],$dataComing)){
	$conmysql_nottest = $con->connecttomysql();
	if($auth->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
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
				$arrayResult['RESPONSE_CODE'] = "5005";
				$arrayResult['RESPONSE_AWARE'] = "insert";
				$arrayResult['RESPONSE'] = "Insert member account !!";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "4003";
			$arrayResult['RESPONSE_AWARE'] = "permission";
			$arrayResult['RESPONSE'] = "Not permission this menu";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "4007";
		$arrayResult['RESPONSE_AWARE'] = "api";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(407);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>