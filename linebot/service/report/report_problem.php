<?php
$pattern = "/[\s:\;\/]/"; 
$filename = basename(__FILE__, '.php');
$arrMessage = preg_split($pattern, $message,-1,PREG_SPLIT_NO_EMPTY);
if(sizeof($arrMessage)>1){
	$member_no = $lineLib->getMemberNo($user_id);
	/*
	if($member_no =='etnmode1'||$member_no =='etnmode2' ||$member_no =='etnmode3' || $member_no =='etnmode4' || $member_no =='dev@mode' || $member_no =='salemode'){
		$messageResponse = "ขอบคุณสำหรับการแจ้งปัญหา ทางเราได้รับเรื่องไว้แล้ว ".$member_no;//.$profile;
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;	
	}else{
		*/
		$insertTextIncome = $conmysql->prepare("INSERT INTO lbreportproblem (text_report,line_token,detail) VALUES (:text,:line_token,:detail)");
		if($insertTextIncome->execute([
			':text' => $arrMessage[1], 
			':line_token' => $user_id,
			':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
		])){ 
			$messageResponse = "ขอบคุณสำหรับการแจ้งปัญหา ทางเราได้รับเรื่องไว้แล้ว";//.$profile;
			$dataPrepare = $lineLib->prepareMessageText($messageResponse);
			$arrPostData["messages"] = $dataPrepare;
			$arrPostData["replyToken"] = $reply_token;	
		}else{
			$data = [
				':text' => $message, 
				':line_token' => $user_id,
				':member_no' => $member_no,
				':detail' => json_encode($dataComing["events"][0],JSON_UNESCAPED_UNICODE )
			];
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0044",
				":error_desc" => "insert ลง  lbreportproblem ไม่ได้ "."\n".'query:'.$query.json_encode($data),
				":error_device" => 'Line Bot'
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "Line Bot insert ลง  lbreportproblem ไม่ได้ ".$filename."\n".'DATA => '.json_encode($data);
			$lib->sendLineNotify($message_error);
		}
	//}
}else{
	$messageResponse = "กรุณาพิมพ์  'แจ้งปัญหา/ปัญหา'  เช่น แจ้งปัญหา/ฉันไม่สามารถผูกบัญชีได้";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;	
}
?>