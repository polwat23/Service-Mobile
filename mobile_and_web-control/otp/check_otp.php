<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['otp','ref_no'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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
		$rowOTP = $checkOTP->fetch(PDO::FETCH_ASSOC);
		$expire = strtotime($rowOTP["expire_date"]);
		if($expire >= $callfile_now){
			if($rowOTP["otp_status"] == '-9'){
				$arrayResult['RESPONSE_CODE'] = "WS0016";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else if($rowOTP["otp_status"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0015";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$updateExpireOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '-1' WHERE refno_otp = :ref_no");
			$updateExpireOTP->execute([':ref_no' => $dataComing["ref_no"]]);
			$arrayResult['RESPONSE_CODE'] = "WS0013";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0012";
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