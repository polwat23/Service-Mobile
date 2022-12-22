<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkKeycode = $conmysql->prepare("SELECT keycode FROM logregisterelection WHERE member_no = :member_no ORDER BY register_date DESC");
		$checkKeycode->execute([':member_no' => $payload["member_no"]]);
		$rowKeycode = $checkKeycode->fetch(PDO::FETCH_ASSOC);
		$getPhoneNumber = $conoracle->prepare("SELECT SMS_MOBILEPHONE FROM mbmembmaster WHERE member_no = :member_no");
		$getPhoneNumber->execute([':member_no' => $member_no ]);
		$rowPhoneNumber = $getPhoneNumber->fetch(PDO::FETCH_ASSOC);
		
		$arrayTel = array();
		$bulkInsert = array();
			
		$arrayComing = array();
		$arrayComing["TEL"] = $rowPhoneNumber["SMS_MOBILEPHONE"];
		$arrayComing["MEMBER_NO"] = $member_no;
		$arrayTel[] = $arrayComing;
			
		$arrayDest["member_no"] = $member_no;
		$arrayDest["tel"] = $rowPhoneNumber["SMS_MOBILEPHONE"];
		$arrayDest["message"] = 'รหัส Pincode '.$rowKeycode["keycode"].' นี้จะใช้ในการลงคะแนนสรรหาวันที่ 13 - 22 ธันวาคม 2565';
		$arraySendSMS = $lib->sendSMS($arrayDest);
		if($arraySendSMS["RESULT"]){
			$arrayLogSMS = $func->logSMSWasSent(null,$arrayDest["message"],$arrayTel,'system');
		}else{
			$bulkInsert[] = "('".$arrayDest["message"]."','".$member_no."',
					'mobile_app',null,null,'ส่ง SMS ไม่ได้เนื่องจาก Service ให้ไปดูโฟลเดอร์ Log'".json_encode($arraySendSMS).",'system',null)";
			$func->logSMSWasNotSent($bulkInsert);
			unset($bulkInsert);
		}
			
		/*
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['action'] = "sendmsg";
		$arrVerifyToken["mode"] = "eachmsg";
		$arrVerifyToken['typeMsg'] = 'OTP';
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
		$arrMsg[0]["msg"] = 'รหัส '.$rowKeycode["keycode"].' รหัสผ่านนี้จะใช้ในการลงคะแนนสรรหาวันที่ 13-16 ธันวาคม 2564';
		$arrMsg[0]["to"] = $rowPhoneNumber["SMS_MOBILEPHONE"];
		$arrSendData["dataMsg"] = $arrMsg;
		$arrSendData["custId"] = 'mhd';
		$arrHeader[] = "version: v1";
		$arrHeader[] = "OAuth: Bearer ".$verify_token;
		$arraySendSMS = $lib->posting_data($config["URL_SMS_ELECTION"].'/navigator',$arrSendData,$arrHeader);
		*/
		
		$arrayResult['REMARK_FORGETPASS'] = "หมายเลขโทรศัพท์ : ".substr($rowPhoneNumber["SMS_MOBILEPHONE"],0,3)."-XXX-X".substr($rowPhoneNumber["SMS_MOBILEPHONE"],7)." (musaving)";
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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