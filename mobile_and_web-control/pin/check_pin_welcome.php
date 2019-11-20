<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no'],$payload)){
	$checkPin = $conmysql->prepare("SELECT pin FROM gcmemberaccount WHERE member_no = :member_no");
	$checkPin->execute([
		':member_no' => $payload["member_no"]
	]);
	$rowPin = $checkPin->fetch();
	// Pin Status : 9 => DEV, 1 => TRUE, 0 => FALSE
	if(isset($rowPin["pin"])){
		if($payload["user_type"] == '9'){
			$arrayResult['RESULT'] = 9;
		}else{
			$arrayResult['RESULT'] = 1;
		}
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = 0;
		echo json_encode($arrayResult);
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