<?php
$insertTextIncome = $conmysql->prepare("INSERT INTO lbrespondmessage (title,line_token,detail) VALUES (:title,:line_token,:detail)");
$filename = basename(__FILE__, '.php');
//if($member_no =='etnmode1'||$member_no =='etnmode2' ||$member_no =='etnmode3' || $member_no =='etnmode4' || $member_no =='dev@mode' || $member_no =='salemode'){
	//none
//}else{	
	if($insertTextIncome->execute([
		':title' => $message, 
		':line_token' => $user_id,
		':detail' => json_encode($arrPostData,JSON_UNESCAPED_UNICODE )
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
			":error_desc" => "insert ลง  lbrespondmessage  ไม่ได้ "."\n".json_encode($data),
			":error_device" => 'Line Bot'
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "Line Bot insert ตาราง  lbrespondmessage  ไม่ได้".$filename."\n".'DATA => '.json_encode($data);
		$lib->sendLineNotify($message_error);	
	}
//}
?>