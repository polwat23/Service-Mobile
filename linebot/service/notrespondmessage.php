<?php
$filename = basename(__FILE__, '.php');
$member_no = $lineLib->getMemberNo($user_id);
if($member_no =='etnmode1'||$member_no =='etnmode2' ||$member_no =='etnmode3' || $member_no =='etnmode4' || $member_no =='dev@mode' || $member_no =='salemode'){
	//none
}else{
	$insertTextIncome = $conmysql->prepare("INSERT INTO lbnotrespondmessage (message,line_token,detail) VALUES (:message,:line_token,:detail)");
	if($insertTextIncome->execute([
		':message' => $message, 
		':line_token' => $user_id,
		':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
	])){
		
		//none
	}else{
		$data = [
			':text' => $message, 
			':line_token' => $user_id,
			':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
		];
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0044",
			":error_desc" => "insert ลง  lbnotrespondmessage  ไม่ได้ "."\n".json_encode($data),
			":error_device" => 'Line Bot'
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "Line Bot insert ตาราง  lbnotrespondmessage  ไม่ได้".$filename."\n".'DATA => '.json_encode($data);
		$lib->sendLineNotify($message_error);
	}
}
?>