<?php
$filename = basename(__FILE__, '.php');
$member_no = $lineLib->getMemberNo($user_id);

	$insertTextIncome = $conmysql->prepare("INSERT INTO lbincometext (text,line_token,detail) VALUES (:text,:line_token,:detail)");
	if($insertTextIncome->execute([
		':text' => $message, 
		':line_token' => $user_id,
		':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
	])){
		/*
		$insertTextIncome = $conmysql->prepare("INSERT INTO lbmessagedata (text,line_token,detail,sender) VALUES (:text,:line_token,:detail,:member_no)");
		if($insertTextIncome->execute([
			':text' => $message, 
			':line_token' => $user_id,
			':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE ),
			':member_no' => $member_no
		])){
			//none
		}else{
			$data = [
				':text' => $message, 
				':line_token' => $user_id,
				':member_no' => $member_no
				//':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
			];
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0044",
				":error_desc" => "insert ลง  lbmessagedata  ไม่ได้ "."\n".'query:'.$query.json_encode($data),
				":error_device" => 'Line Bot'
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "Line Bot insert ลง  lbmessagedata  ไม่ได้".$filename."\n".'DATA => '.json_encode($data);
			$lib->sendLineNotify($message_error);
		}
		//none
		
		*/
	}else{
		$data = [
			':text' => $message, 
			':line_token' => $user_id,
			':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
		];
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0044",
			":error_desc" => "insert ลง  lbincometext  ไม่ได้ "."\n".'query:'.$query.json_encode($data),
			":error_device" => 'Line Bot'
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "Line Bot insert ลง  lbincometext  ไม่ได้".$filename."\n".'DATA => '.json_encode($data);
		$lib->sendLineNotify($message_error);
	}

?>