<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','phone','password','api_token','unique_id'],$dataComing)){
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
	$phone = $dataComing["phone"];
	$checkPhoneNumber = $conoracle->prepare("SELECT TRIM(mem_telmobile) as mem_telmobile FROM mbmembmaster WHERE member_no = :member_no");
	$checkPhoneNumber->execute([':member_no' => $dataComing["member_no"]]);
	$rowNumber = $checkPhoneNumber->fetch(PDO::FETCH_ASSOC);
	if(empty($rowNumber["MEM_TELMOBILE"])){
		$arrayResult['RESPONSE_CODE'] = "WS0017";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
	if($rowNumber["MEM_TELMOBILE"] != $phone){
		$arrayResult['RESPONSE_CODE'] = "WS0059";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
	$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
	$conmysql->beginTransaction();
	$insertAccount = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,register_channel,deptaccount_no_regis) 
										VALUES(:member_no,:password,:phone,:email,:channel,:deptaccount_no)");
	if($insertAccount->execute([
		':member_no' => $dataComing["member_no"],
		':password' => $password,
		':phone' => $phone,
		':email' => $email,
		':channel' => $dataComing["channel"],
		':deptaccount_no' => $dataComing["deptaccount_no"]
	])){
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($dataComing["member_no"],-6);
		$arrDataAPI["CitizenID"] = $dataComing["card_person"];
		$arrDataAPI["CoopAccountNo"] = $dataComing["deptaccount_no"];
		$arrDataAPI["UserRequestDate"] = date('c');
		$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."MemberProfile/SaveMember",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."MemberProfile/SaveMember",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."MemberProfile/SaveMember";
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			$conmysql->commit();
			$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
			$arrayResult['PASSWORD'] = $dataComing["password"];
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$conmysql->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1031",
				":error_desc" => "Error ขาติดต่อ Server เงินฝาก Egat"."\n".json_encode($arrResponseAPI),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$arrayResult['RESPONSE_CODE'] = "WS1031";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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