<?php
$filename = basename(__FILE__, '.php');

if(isset($message) &&  $message !=""){
$member_no = $lineLib->getMemberNo($user_id);
//if($member_no =='etnmode1'||$member_no =='etnmode2' ||$member_no =='etnmode3' || $member_no =='etnmode4' || $member_no =='dev@mode' || $member_no =='salemode'){
	//none
//}else{
	$insertTextIncome = $conmysql->prepare("INSERT INTO lbincometext (text,line_token,detail) VALUES (:text,:line_token,:detail)");
	if($insertTextIncome->execute([
		':text' => $message, 
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
			":error_desc" => "insert ลง  lbincometext  ไม่ได้ "."\n".'query:'.$query.json_encode($data),
			":error_device" => 'Line Bot'
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "Line Bot insert ลง  lbincometext  ไม่ได้".$filename."\n".'DATA => '.json_encode($data);
		$lib->sendLineNotify($message_error);
	}
//}
}

?>