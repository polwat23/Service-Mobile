<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['password'],$dataComing)){
	$getOldPassword = $conmysql->prepare("SELECT password,temppass,account_status FROM gcmemberaccount 
											WHERE member_no = :member_no");
	$getOldPassword->execute([':member_no' => $payload["member_no"]]);
	if($getOldPassword->rowCount() > 0){
		$rowAccount = $getOldPassword->fetch(PDO::FETCH_ASSOC);
		if($rowAccount['account_status'] == '-9'){
			$validpassword = password_verify($dataComing["password"], $rowAccount['temppass']);
		}else{
			$validpassword = password_verify($dataComing["password"], $rowAccount['password']);
		}
		if($validpassword){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0004";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
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