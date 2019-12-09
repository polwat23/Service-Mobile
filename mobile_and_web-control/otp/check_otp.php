<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['otp','ref_no'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
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
				$arrayResult['RESPONSE_CODE'] = "WS0027";
				$arrayResult['RESPONSE_MESSAGE'] = "OTP นี้ได้ถูกยกเลิกไปแล้วเนื่องจากท่านขอ OTP ใหม่";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else if($rowOTP["otp_status"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0028";
				$arrayResult['RESPONSE_MESSAGE'] = "OTP ถูกใช้งานแล้ว";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else if($rowOTP["otp_status"] == '0'){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0029";
				$arrayResult['RESPONSE_MESSAGE'] = "OTP ไม่สามารถใช้งานได้กรุณากดส่งอีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$updateExpireOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '-1' WHERE refno_otp = :ref_no");
			$updateExpireOTP->execute([':ref_no' => $dataComing["ref_no"]]);
			$arrayResult['RESPONSE_CODE'] = "WS0026";
			$arrayResult['RESPONSE_MESSAGE'] = "OTP หมดเวลาการใช้งาน";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0022";
		$arrayResult['RESPONSE_MESSAGE'] = "OTP ไม่ถูกต้อง";
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