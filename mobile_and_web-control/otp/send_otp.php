<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','tel','menu_component'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$conmysql->beginTransaction();
	$member_no = strtolower(str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT));
	$getFCMToken = $conmysql->prepare("SELECT gtk.fcm_token,gul.member_no FROM gcuserlogin gul LEFT JOIN gctoken gtk ON gul.id_token = gtk.id_token 
										WHERE gul.receive_notify_transaction = '1' and gul.member_no = :member_no
										and gul.is_login = '1' and gtk.fcm_token IS NOT NULL");
	$getFCMToken->execute([':member_no' => 'dev@mode']);
	if($getFCMToken->rowCount() > 0){
		$rowFCMToken = $getFCMToken->fetch();
		$getOTPTemplate = $conmysql->prepare("SELECT subject,body FROM smssystemtemplate 
											WHERE component_system = :menu_component and is_use = '1'");
		$getOTPTemplate->execute([':menu_component' => $dataComing["menu_component"]]);
		$rowOTPTemplate = $getOTPTemplate->fetch();
		$otp_password = $lib->randomText('number',6);
		$reference = $lib->randomText('all',10);
		$duration_expire = $func->getConstant('duration_otp_expire') ? $func->getConstant('duration_otp_expire') : '15';
		$expire_date = date('Y-m-d H:i:s',strtotime('+'.$duration_expire.' minutes'));
		$arrTarget["RANDOM_NUMBER"] = $otp_password;
		$arrTarget["RANDOM_ALL"] = $reference;
		$arrTarget["DATE_EXPIRE"] = $lib->convertdate($expire_date,'D m Y',true);
		$arrMessage = $lib->mergeTemplate($rowOTPTemplate["subject"],$rowOTPTemplate["body"],$arrTarget);
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
				$arrayResult['TEL'] = $dataComing["tel"];
				$arrayResult['TEL_FORMAT'] = $lib->formatphone($dataComing["tel"]);
				$arrayResult['VERIFY'] = TRUE;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0025";
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถส่ง OTP ได้กรุณาติดต่อเจ้าหน้าที่สหกรณ์";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS0024";
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเก็บ OTP ได้กรุณาติดต่อเจ้าหน้าที่สหกรณ์";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0023";
		$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบเบอร์โทรศัพท์ของคุณอยู่ในฐานข้อมูล";
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