<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['member_no','unique_id','phone','email'],$dataComing)){
	$password = $lib->randomText('all',6);
	$phone = preg_replace('/-/', '', $dataComing["phone"]);
	$password_temp = password_hash($password, PASSWORD_DEFAULT);
	$email = isset($dataComing["email"]) ? preg_replace('/\s+/', '', $dataComing["email"]) : null;
	$phone = (explode(',',$phone))[0];

	$insertAccount = $conmssql->prepare("INSERT INTO gcmemberaccount(member_no,phone_number,email,temppass,password,account_status,prev_acc_status) 
										VALUES(?,?,?,?,?,'-9','-9')");
	if($insertAccount->execute([$dataComing["member_no"],$phone,$email, $password_temp,$password_temp])){
		$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
		$arrayResult['PASSWORD'] = $password;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		/*$filename = basename(__FILE__, '.php');
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
		$lib->sendLineNotify($message_error);*/
		$arrayResult['RESPONSE_CODE'] = "WS1018";
		$arrayResult['RESPONSE_MESSAGE'] = "เกิดข้อผิดพลาดบางประการทำให้ไม่สามารถสมัครได้ในขณะนี้ กรุณาติดต่อสหกรณ์ ";
		$arrayResult['RESPONSESS'] =[$dataComing["member_no"],$phone,$email, $password_temp,$password_temp];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	/*$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);*/
	//$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>