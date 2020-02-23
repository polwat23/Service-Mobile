<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','tel','ref_old_otp','menu_component'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$conmysql->beginTransaction();
	$member_no = strtolower(str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT));
	$getFCMToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
										WHERE gul.receive_notify_transaction = '1' and gul.member_no = :member_no and gtk.at_is_revoke = '0' and gul.channel = 'mobile_app'
										and gul.is_login = '1' and gtk.fcm_token IS NOT NULL ORDER BY gul.id_userlogin DESC");
	$getFCMToken->execute([':member_no' => 'dev@mode']);
	if($getFCMToken->rowCount() > 0){
		$rowFCMToken = $getFCMToken->fetch(PDO::FETCH_ASSOC);
		$updateOldOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '-9' WHERE refno_otp = :ref_old_otp");
		$updateOldOTP->execute([':ref_old_otp' => $dataComing["ref_old_otp"]]);
		$templateMessage = $func->getTemplatSystem("OTPChecker",1);
		$otp_password = $lib->randomText('number',6);
		$reference = $lib->randomText('all',10);
		$duration_expire = $func->getConstant('duration_otp_expire') ? $func->getConstant('duration_otp_expire') : '15';
		$expire_date = date('Y-m-d H:i:s',strtotime('+'.$duration_expire.' minutes'));
		$arrTarget["RANDOM_NUMBER"] = $otp_password;
		$arrTarget["RANDOM_ALL"] = $reference;
		$arrTarget["DATE_EXPIRE"] = $lib->convertdate($expire_date,'D m Y',true);
		$arrMessage = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$arrTarget);
		$arrPayloadNotify["TO"][] = $rowFCMToken["fcm_token"];
		$arrPayloadNotify["MEMBER_NO"] = $rowFCMToken["member_no"];
		$arrPayloadNotify["PAYLOAD"] = $arrMessage;
		$insertOTP = $conmysql->prepare("INSERT INTO gcotp(refno_otp,otp_password,destination_number,expire_date,otp_text)
											VALUES(:ref_otp,:otp_pass,:destination,:expire_date,:otp_text)");
		if($insertOTP->execute([
			':ref_otp' => $reference,
			':otp_pass' => $otp_password,
			':destination' => $dataComing["tel"],
			':expire_date' => $expire_date,
			':otp_text' => $arrMessage["BODY"]
		])){
			if($lib->sendNotify($arrPayloadNotify,'person')){
				$conmysql->commit();
				$arrayResult['REFERENCE_OTP'] = $reference;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0018";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrExecute = [
				':ref_otp' => $reference,
				':otp_pass' => $otp_password,
				':destination' => $dataComing["tel"],
				':expire_date' => $expire_date,
				':otp_text' => $arrMessage["BODY"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertOTP;
			$arrError["ERROR_CODE"] = 'WS1011';
			$lib->addLogtoTxt($arrError,'otp_error');
			$arrayResult['RESPONSE_CODE'] = "WS1001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0017";
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