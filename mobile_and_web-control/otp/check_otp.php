<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['otp','ref_no'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS0001";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS0001";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$callfile_now = strtotime(date('Y-m-d H:i:s'));
	$checkOTP = $conmysql->prepare("SELECT otp_status,expire_date FROM gcotp WHERE otp_password = :otp_pass and refno_otp = :ref_no");
	$checkOTP->execute([
		':otp_pass' => $dataComing["otp"],
		':ref_no' => $dataComing["ref_no"]
	]);
	if($checkOTP->rowCount() > 0){
		$rowOTP = $checkOTP->fetch();
		$expire = strtotime($rowOTP["expire_date"]);
		if($expire >= $callfile_now){
			if($rowOTP["otp_status"] == '-9'){
				$arrayResult['RESPONSE_CODE'] = "WS0016";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "OTP ถูกยกเลิกเนื่องจากมีการขอ OTP ใหม่แล้ว";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "OTP was cancel because OTP has resend";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else if($rowOTP["otp_status"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0015";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "OTP ถูกใช้งานแล้ว";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "OTP has been use";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else if($rowOTP["otp_status"] == '0'){
				$updateUseOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '1' WHERE refno_otp = :ref_no");
				$updateUseOTP->execute([':ref_no' => $dataComing["ref_no"]]);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0033";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "OTP ไม่สามารถใช้งานได้กรุณากดส่งอีกครั้ง";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "OTP cannot use please resend";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$updateExpireOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '-1' WHERE refno_otp = :ref_no");
			$updateExpireOTP->execute([':ref_no' => $dataComing["ref_no"]]);
			$arrayResult['RESPONSE_CODE'] = "WS0013";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "OTP หมดเวลาการใช้งาน";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "OTP was expired";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0012";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "OTP ไม่ถูกต้อง";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "OTP is invalid";
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