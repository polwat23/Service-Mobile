<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no'],$payload)){
	$checkPinNull = $conmysql->prepare("SELECT pin FROM gcmemberaccount WHERE member_no = :member_no and account_status NOT IN('-6','-7','-8')");
	$checkPinNull->execute([':member_no' => $payload["member_no"]]);
	$rowPinNull = $checkPinNull->fetch();
	if(isset($rowPinNull["pin"])){
		$checkPin = $conmysql->prepare("SELECT account_status FROM gcmemberaccount WHERE member_no = :member_no and pin = :pin");
		$checkPin->execute([
			':member_no' => $payload["member_no"],
			':pin' => $dataComing["pin"]
		]);
		if($checkPin->rowCount() > 0){
			$rowaccount = $checkPin->fetch();
			if($rowaccount["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "4003";
			$arrayResult['RESPONSE_AWARE'] = "pin";
			$arrayResult['RESPONSE'] = "Invalid Pin";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$updatePin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin WHERE member_no = :member_no");
		if($updatePin->execute([
			':pin' => $dataComing["pin"],
			':member_no' => $payload["member_no"]
		])){
			$fetchAcc = $conmysql->prepare("SELECT account_status FROM gcmemberaccount WHERE member_no = :member_no");
			$fetchAcc->execute([
				':member_no' => $payload["member_no"]
			]);
			$rowaccount = $fetchAcc->fetch();
			if($rowaccount["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "update";
			$arrayResult['RESPONSE'] = "Update Pin Failed";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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