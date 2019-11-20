<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no'],$payload)){
	$conmysql->beginTransaction();
	$updateResetPin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = null WHERE member_no = :member_no");
	if($updateResetPin->execute([
		':member_no' => $payload["member_no"]
	])){
		if($func->logoutAll(null,$payload["member_no"],'-10',$conmysql)){
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS1016";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot reset pin because cannot logout";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS1015";
		$arrayResult['RESPONSE_MESSAGE'] = "Cannot reset pin";
		$arrayResult['RESULT'] = FALSE;
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