<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['password'],$dataComing)){
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
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "รหัสผ่านไม่ตรงกับรหัสเดิม";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Password does not match";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลผู้ใช้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Not found membership";
		}
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>