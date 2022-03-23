<?php  
if($checkBeenBind->rowCount() > 0){
	$data = "ท่านได้ผูกบัญชีแล้ว สามารถดูข้อมูลได้แล้ว";
	$dataTemplate = $lineLib->mergeTextMessage($data);
	$arrPostData['messages'][0] = $dataTemplate;
	$arrPostData['replyToken'] = $reply_token;
}else{
	if($confirmOtp =='0'){
		if($arrMessage[1] == $meberData["otp"]){
			$updataOtp = $conmysql->prepare("UPDATE  kfinancelinememberaccount SET confirm_otp = :confirm_otp");
			if($updataOtp->execute([
				':confirm_otp' => '1'
			])){
				$ms = "ผูกบัญชีสำเร็จ";
				$messageOTP = $lineLib->mergeTextMessage($ms);
				$arrPostData['messages'][0] = $messageOTP;
				$arrPostData['replyToken'] = $reply_token;
			}else{
				$arrPostData['replyToken'] = $updataOtp->execute([
				':confirm_otp' => '1'
			]);}
			
		}else{
			$ms = "รหัส opt ไม่ถูกต้อง";
			$messageOTP = $lineLib->mergeTextMessage($ms);
			$arrPostData['messages'][0] = $messageOTP;
			$arrPostData['replyToken'] = $reply_token;
		}
	}
}
?>