<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','tel','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','validateotp')){
		$conmysql->beginTransaction();
		$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		$templateMessage = $func->getTemplateSystem("OTPValidate",1);
		$otp_password = $lib->randomText('number',6);
		$reference = $lib->randomText('all',6);
		$duration_expire = $func->getConstant('duration_otp_expire') ? $func->getConstant('duration_otp_expire') : '5';
		$expire_date = date('Y-m-d H:i:s',strtotime('+'.$duration_expire.' minutes'));
		$arrTarget["RANDOM_NUMBER"] = $otp_password;
		$arrTarget["RANDOM_ALL"] = $reference;
		$arrTarget["DATE_EXPIRE"] = $lib->convertdate($expire_date,'D m Y',true);
		$arrMessage = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$arrTarget);
		$arrayComing["TEL"] = $dataComing["tel"];
		$arrayComing["MEMBER_NO"] = $member_no;
		$arrayTel[] = $arrayComing;
		$bulkInsert = array();
		$arrayDest = array();
		if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != "" && mb_strlen($arrayTel[0]["TEL"]) == 10){
			$insertOTP = $conmysql->prepare("INSERT INTO gcotp(refno_otp,otp_password,destination_number,expire_date,otp_text)
												VALUES(:ref_otp,:otp_pass,:destination,:expire_date,:otp_text)");
			if($insertOTP->execute([
				':ref_otp' => $reference,
				':otp_pass' => $otp_password,
				':destination' => $arrayTel[0]["TEL"],
				':expire_date' => $expire_date,
				':otp_text' => $arrMessage["BODY"]
			])){
				$arrVerifyToken['exp'] = time() + 300;
				$arrVerifyToken['action'] = "sendmsg";
				$arrVerifyToken["mode"] = "eachmsg";
				$arrVerifyToken['typeMsg'] = 'OTP';
				$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
				$arrMsg[0]["msg"] = $arrMessage["BODY"];
				$arrMsg[0]["to"] = $arrayTel[0]["TEL"];
				$arrSendData["dataMsg"] = $arrMsg;
				$arrSendData["custId"] = 'nhp';
				$arrHeader[] = "version: v1";
				$arrHeader[] = "OAuth: Bearer ".$verify_token;
				$arraySendSMS = $lib->posting_data($config["URL_SMS"].'/navigator',$arrSendData,$arrHeader);
				if($arraySendSMS["RESULT"]){
					$arrayLogSMS = $func->logSMSWasSent(null,$arrMessage["BODY"],$arrayTel,'system');
					$conmysql->commit();
					$arrayResult['REFERENCE_OTP'] = $reference;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$bulkInsert[] = "('".$arrMessage["BODY"]."','".$member_no."',
							'mobile_app',null,null,'ส่ง SMS ไม่ได้เนื่องจาก Service ให้ไปดูโฟลเดอร์ Log','system',null)";
					$func->logSMSWasNotSent($bulkInsert);
					unset($bulkInsert);
					$bulkInsert = array();
					$conmysql->rollback();
					$arrayResult['RESPONSE_MESSAGE'] = "ส่ง SMS ไม่ได้กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_MESSAGE'] = "ส่ง SMS ไม่ได้กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบเบอร์โทรศัพท์";
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