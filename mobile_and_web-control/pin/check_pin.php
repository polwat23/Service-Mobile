<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no'],$payload) && $lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkPin = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE pin = :pin and member_no = :member_no");
	$checkPin->execute([
		':pin' => $dataComing["pin"],
		':member_no' => $payload["member_no"]
	]);
	if($checkPin->rowCount() > 0){
		$rowaccount = $checkPin->fetch();
		$arrayResult['RESULT'] = TRUE;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
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