<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['otp','ref_no'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	$callfile_now = strtotime(date('Y-m-d H:i:s'));
	$checkOTP = $conoracle->prepare("SELECT otp_status,expire_date FROM gcotp WHERE TRIM(otp_password) = :otp_pass and TRIM(refno_otp) = :ref_no");
	$checkOTP->execute([
		':otp_pass' => $dataComing["otp"],
		':ref_no' => $dataComing["ref_no"]
	]);
	$rowOTP = $checkOTP->fetch(PDO::FETCH_ASSOC);
	if(isset($rowOTP["EXPIRE_DATE"])){
		$expire = strtotime($rowOTP["EXPIRE_DATE"]);
		if($expire >= $callfile_now){
			if($rowOTP["OTP_STATUS"] == '-9'){
				$arrayResult['RESPONSE_CODE'] = "WS0016";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else if($rowOTP["OTP_STATUS"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0015";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else if($rowOTP["OTP_STATUS"] == '0'){
				$updateUseOTP = $conoracle->prepare("UPDATE gcotp SET otp_status = '1' WHERE refno_otp = :ref_no");
				$updateUseOTP->execute([':ref_no' => $dataComing["ref_no"]]);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0033";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$updateExpireOTP = $conoracle->prepare("UPDATE gcotp SET otp_status = '-1' WHERE refno_otp = :ref_no");
			$updateExpireOTP->execute([':ref_no' => $dataComing["ref_no"]]);
			$arrayResult['RESPONSE_CODE'] = "WS0013";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0012";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>