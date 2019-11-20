<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no'],$payload) && $lib->checkCompleteArgument(['password'],$dataComing)){
	$getOldPassword = $conmysql->prepare("SELECT password,temppass,account_status FROM gcmemberaccount 
											WHERE member_no = :member_no");
	$getOldPassword->execute([':member_no' => $payload["member_no"]]);
	if($getOldPassword->rowCount() > 0){
		$rowAccount = $getOldPassword->fetch();
		if($rowAccount['account_status'] == '-9'){
			if($dataComing["password"] == $rowAccount["temppass"]){
				$validpassword = true;
			}else{
				$validpassword = false;
			}
		}else{
			$validpassword = password_verify($dataComing["password"], $rowAccount['password']);
		}
		if($validpassword){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0004";
			$arrayResult['RESPONSE_MESSAGE'] = "Password was wrong";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		http_response_code(204);
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