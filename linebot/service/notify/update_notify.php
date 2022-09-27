<?php
$data = $lineLib->getMemberNo($user_id);
$member_no = $data;
if($message == " เปิดการแจ้งเตือน"){
	$is_notify = 1;
}else{
	$is_notify = 0;
}

$insertNotify =  $conmysql->prepare("UPDATE lbnotify SET is_notify = :is_notify WHERE line_token = :line_token ");
	if($insertNotify->execute([
		':is_notify' => $is_notify,
		':line_token' => $user_id
	])){
		$messageResponse = $message." สำเร็จ";
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
	}else{
		$message_error = "Line Bot insert ลง  lbnotify  ไม่ได้".$filename."\n".'sql => '.$insertNotify;
		$lib->sendLineNotify($message_error);
	}
?>