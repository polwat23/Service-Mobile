<?php
	$data["meber_no"] = $lineLib->getMemberNo($user_id);
	$update_lineToken = $conmysql->prepare("UPDATE gcmemberaccount 
											SET line_token = NULL
											WHERE  line_token = :line_token");
	if($update_lineToken->execute([
		':line_token' => $user_id 
	])){
		$messageResponse = "ยกเลิกผูกบัญชีสำเร็จ";
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
		/*
		$updateOtp = $conmysql->prepare("UPDATE lbotp SET confirm = '0' WHERE line_token = :line_token" );
		if($updateOtp->execute([
			':line_token' => $user_id 
		])){
			$messageResponse = "ยกเลิกผูกบัญชีสำเร็จ";
			$dataPrepare = $lineLib->prepareMessageText($messageResponse);
			$arrPostData["messages"] = $dataPrepare;
			$arrPostData["replyToken"] = $reply_token;
		}
		*/
	}else{
		$data["sql"] = $update_lineToken;
		$log->writeLog('errorusage',$logStruc);
		$message_error = "Line Bot ยกเลิกผูกบัญชีไม่สำเร็จ".$filename."\n"."DATA => ".json_encode($data);
		$lib->sendLineNotify($message_error);
	
		
		$messageResponse = "ไม่สามารถยกเลิกผูกบัญชีได้";
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
	}
?>