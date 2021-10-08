<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','value_election','tel_mobile'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Election')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$updateFlag = $conoracle->prepare("UPDATE MBMEMBELECTION SET POST_NO = :value_election WHERE ELECTION_YEAR = EXTRACT(YEAR FROM SYSDATE) + 543 AND MEMBER_NO = :member_no");
		if($updateFlag->execute([
			':value_election' => $dataComing["value_election"],
			':member_no' => $member_no
		])){
			$keycode = $lib->randomText('number',6);
			$delDup = $conmysql->prepare("DELETE FROM logregisterelection WHERE member_no = :member_no");
			$delDup->execute([':member_no' => $payload["member_no"]]);
			$insertLog = $conmysql->prepare("INSERT INTO logregisterelection(member_no,keycode,tel_mobile,value_election,id_token,app_version)
															VALUES(:member_no,:keycode,:tel_mobile,:value_election,:id_token,:app_version)");
			$insertLog->execute([
				':member_no' => $payload["member_no"],
				':keycode' => $keycode,
				':tel_mobile' => $dataComing["tel_mobile"],
				':value_election' => $dataComing["value_election"],
				':id_token' => $payload["id_token"],
				':app_version' => $dataComing["app_version"] ?? 'web'
			]);
			$arrVerifyToken['exp'] = time() + 300;
			$arrVerifyToken['action'] = "sendmsg";
			$arrVerifyToken["mode"] = "eachmsg";
			$arrVerifyToken['typeMsg'] = 'OTP';
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
			$arrMsg[0]["msg"] = 'รหัสของท่านคือ : '.$keycode.' รหัสผ่านชุดนี้ใช้ในการลงคะแนนสรรหา วันที่ 13-16 ธันวาคม 2564';
			$arrMsg[0]["to"] = $dataComing["tel_mobile"];
			$arrSendData["dataMsg"] = $arrMsg;
			$arrSendData["custId"] = 'mhd';
			$arrHeader[] = "version: v1";
			$arrHeader[] = "OAuth: Bearer ".$verify_token;
			$arraySendSMS = $lib->posting_data($config["URL_SMS_ELECTION"].'/navigator',$arrSendData,$arrHeader);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "EC0001";
			$arrayResult['RESPONSE_MESSAGE'] = $configError["ELECTION"][0]["ELECTION_ERR"][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
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