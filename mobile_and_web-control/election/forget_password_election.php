<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkKeycode = $conmysql->prepare("SELECT keycode,tel_mobile FROM logregisterelection WHERE member_no = :member_no ORDER BY register_date DESC");
		$checkKeycode->execute([':member_no' => $payload["member_no"]]);
		$rowKeycode = $checkKeycode->fetch(PDO::FETCH_ASSOC);
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['action'] = "sendmsg";
		$arrVerifyToken["mode"] = "eachmsg";
		$arrVerifyToken['typeMsg'] = 'OTP';
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
		$arrMsg[0]["msg"] = 'รหัส '.$rowKeycode["keycode"].' รหัสผ่านนี้จะใช้ในการลงคะแนนสรรหาวันที่ 13-16 ธันวาคม 2564';
		$arrMsg[0]["to"] = $rowKeycode["tel_mobile"];
		$arrSendData["dataMsg"] = $arrMsg;
		$arrSendData["custId"] = 'mhd';
		$arrHeader[] = "version: v1";
		$arrHeader[] = "OAuth: Bearer ".$verify_token;
		$arraySendSMS = $lib->posting_data($config["URL_SMS_ELECTION"].'/navigator',$arrSendData,$arrHeader);
		$arrayResult['REMARK_FORGETPASS'] = "หมายเลขโทรศัพท์ : ".substr($rowKeycode["tel_mobile"],0,3)."-XXX-X".substr($rowKeycode["tel_mobile"],7)." (Thaicoop)";
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