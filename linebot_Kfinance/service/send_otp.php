<?php
	$arrVerifyToken['exp'] = time() + 300;
	$arrVerifyToken['action'] = "sendmsg";
	$arrVerifyToken["mode"] = "eachmsg";
	$arrVerifyToken['typeMsg'] = 'OTP';
	$verify_token =  $jwt_token->customPayload($arrVerifyToken, 'FykDK4T%bPSk');
	$arrMsg[0]["msg"] = 'รหัส OTP คือ 888999';
	$arrMsg[0]["to"] = '0992409191';
	$arrSendData["dataMsg"] = $arrMsg;
	$arrSendData["custId"] = 'mhd';
	$arrHeader[] = "version: v1";
	$arrHeader[] = "OAuth: Bearer ".$verify_token;
	$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/navigator',$arrSendData,$arrHeader);
	if($arraySendSMS["result"]){
		$data = "กรุณาพิมพ์รหัส OTP เช่น 'otp/รหัสotp' เช่น: otp/123456";
		$dataTemplate = $lineLib->mergeTextMessage($data);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}else{
		$data = "ส่งไม่ได้";
		$dataTemplate = $lineLib->mergeTextMessage($data);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}
?>