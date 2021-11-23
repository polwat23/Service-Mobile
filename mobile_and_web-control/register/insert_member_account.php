<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','password','api_token','unique_id'],$dataComing)){
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
	$email = isset($dataComing["email"]) ? preg_replace('/\s+/', '', $dataComing["email"]) : null;
	if(isset($email) && $email != ""){
		$memberInfo = $conmssqlcoop->prepare("SELECT EMAIL FROM COCOOPTATION WHERE member_id = :member_no");
		$memberInfo->execute([':member_no' => $dataComing["member_no"]]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$insertChangeData = $conmssql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
												VALUES(:member_no,:old_email,:email,'email')");
		$insertChangeData->execute([
			':member_no' => $payload["member_no"],
			':old_email' => $rowMember["EMAIL"] ?? null,
			':email' => $email
		]);
	}
	$phone = $dataComing["phone"];
	if(isset($phone) && $phone != ""){
		$memberInfo = $conmssqlcoop->prepare("SELECT TELEPHONE FROM COCOOPTATION WHERE member_id = :member_no");
		$memberInfo->execute([':member_no' => $dataComing["member_no"]]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$insertChangeData = $conmssql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
												VALUES(:member_no,:old_tel,:tel,'tel')");
		$insertChangeData->execute([
			':member_no' => $payload["member_no"],
			':old_tel' => $rowMember["TELEPHONE"],
			':tel' => $phone
		]);
	}
	$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
	$insertAccount = $conmssql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,register_channel) 
										VALUES(:member_no,:password,:phone,:email,:channel)");
	if($insertAccount->execute([
		':member_no' => $dataComing["member_no"],
		':password' => $password,
		':phone' => $phone,
		':email' => $email,
		':channel' => $dataComing["channel"]
	])){
		$template = $func->getTemplateSystem('NotifyStaffUpdateData');
		$arrayDataTemplate = array();
		$arrayDataTemplate["MEMBER_NO"] = $payload["member_no"];
		$arrayDataTemplate["DEVICE_NAME"] = $dataComing["device_name"].' / On app version => '.$dataComing["app_version"];
		$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
		
		$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
		$arrMailStatus = $lib->sendMail($config["MAIL_FOR_NOTI"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction);
		$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
		$arrayResult['PASSWORD'] = $dataComing["password"];
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS1018",
			":error_desc" => "ไม่สามารถสมัครได้ "."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "ไม่สามารถสมัครได้เพราะ Insert ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$insertAccount->queryString."\n"."Param => ". json_encode([
			':member_no' => $dataComing["member_no"],
			':password' => $password,
			':phone' => $phone,
			':email' => $email,
			':channel' => $dataComing["channel"]
		]);
		$lib->sendLineNotify($message_error);
		$arrayResult['RESPONSE_CODE'] = "WS1018";
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