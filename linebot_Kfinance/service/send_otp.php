<?php
	$arrVerifyToken['exp'] = time() + 300;
			$arrVerifyToken['action'] = "sendmsg";
			$arrVerifyToken["mode"] = "eachmsg";
			$arrVerifyToken['typeMsg'] = 'OTP';
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, 'FykDK4T%bPSk');
			$arrMsg[0]["msg"] = 'รหัส OTP คือ 999999';
			$arrMsg[0]["to"] = '0992409191';
			$arrSendData["dataMsg"] = $arrMsg;
			$arrSendData["custId"] = 'mhd';
			$arrHeader[] = "version: v1";
			$arrHeader[] = "OAuth: Bearer ".$verify_token;
			$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/beta/navigator',$arrSendData,$arrHeader);
		
	if($arraySendSMS["result"]){
		$arrResult = json_decode($arraySendSMS,true);
		if($arrResult["result"]){
			$data = 'รหัส otp ของท่านคือ ';
			$dataTemplate = $lineLib->mergeTextMessage($data);
			$arrPostData['messages'][0] = $dataTemplate;
			$arrPostData['replyToken'] = $reply_token;
		}else{
			file_put_contents(__DIR__.'/log_sent_otp.txt', json_encode($arraySendSMS,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
		}
	}else{
		$data = "ขออภัยความไม่สะดวก ไม่สามารถส่งรหัส OTP ให้ท่านได้";
		$dataTemplate = $lineLib->mergeTextMessage($data);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
		file_put_contents(__DIR__.'/log_sent_otp.txt', json_encode($arraySendSMS,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
	}
?>