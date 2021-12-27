<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','otp','ref_no','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','validateotp')){
		$callfile_now = strtotime(date('Y-m-d H:i:s'));
		$checkOTP = $conmysql->prepare("SELECT otp_status,expire_date,destination_number FROM gcotp WHERE otp_password = :otp_pass and refno_otp = :ref_no");
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
					$arrayResult['RESPONSE_MESSAGE'] = "OTP นี้ถูกยกเลิก";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}else if($rowOTP["otp_status"] == '1'){
					$arrayResult['RESPONSE_CODE'] = "WS0015";
					$arrayResult['RESPONSE_MESSAGE'] = "OTP นี้ถูกใช้งานแล้ว";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}else if($rowOTP["otp_status"] == '0'){
					$updateUseOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '1' WHERE refno_otp = :ref_no");
					$updateUseOTP->execute([':ref_no' => $dataComing["ref_no"]]);
					$updateTel = $conmssql->prepare("UPDATE mbmembmaster SET mem_telmobile = :tel WHERE member_no = :member_no");
					$updateTel->execute([
						':tel' => $rowOTP["destination_number"],
						':member_no' => $dataComing["member_no"]
					]);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0033";
					$arrayResult['RESPONSE_MESSAGE'] = "OTP ไม่สามารถใช้งานได้";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}
			}else{
				$updateExpireOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '-1' WHERE refno_otp = :ref_no");
				$updateExpireOTP->execute([':ref_no' => $dataComing["ref_no"]]);
				$arrayResult['RESPONSE_MESSAGE'] = "OTP นี้หมดอายุแล้ว";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบ OTP";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>