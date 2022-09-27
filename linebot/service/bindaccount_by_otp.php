<?php
session_start();
$geMeberData = $conmysql->prepare("SELECT member_no,phone_number FROM gcmemberaccount WHERE member_no = :member_no AND phone_number = :phone_number");
$geMeberData->execute([
	':member_no' => $arrMessage[0],
	':phone_number' => $arrMessage[1]
]);
$memberData = $geMeberData->fetch(PDO::FETCH_ASSOC);
if($arrMessage[0] == $memberData["member_no"] && $arrMessage[1] == $memberData["phone_number"]){
	$checkOtp = $conmysql->prepare("SELECT otp,confirm FROM lbotp WHERE member_no = :member_no ");
	$checkOtp->execute([
		':member_no' => $memberData["member_no"]
	]);
	if($checkOtp->rowCount() > 0){
		$OtpData = $checkOtp->fetch(PDO::FETCH_ASSOC);
		if($OtpData["confirm"] == '0'){
			$otp = $lib->randomText('number',6);
			$updateOtp = $conmysql->prepare("UPDATE lbotp SET otp = :otp,line_token = :line_token WHERE member_no = :member_no" );
			if($updateOtp->execute([
				':member_no' => $memberData["member_no"], 
				':otp' => $otp,
				':line_token' => $user_id
			])){
				$messageResponse = 'กรุณาพิมพ์รหัสOTP เช่น otp/'.$otp;
				$dataPrepare = $lineLib->prepareMessageText($messageResponse);
				$arrPostData["messages"] = $dataPrepare;
				$arrPostData["replyToken"] = $reply_token;	
			}else{
				$arrPostData['replyToken'] = $updateOtp;
			}
		}else{
			$messageResponse = 'ท่านได้ยืนยัน OTP สำเร็จแล้ว';
			$dataPrepare = $lineLib->prepareMessageText($messageResponse);
			$arrPostData["messages"] = $dataPrepare;
			$arrPostData["replyToken"] = $reply_token;	
		}	
	}else{
		$otp = $lib->randomText('number',6);
		$insertOtp = $conmysql->prepare("INSERT INTO lbotp (member_no,otp,line_token) VALUES (:member_no,:otp,:line_token)");
		if($insertOtp->execute([
			':member_no' => $memberData["member_no"], 
			':otp' => $otp,
			':line_token' => $user_id
		])){
			//$messageResponse = 'ท่านคือ '.$memberData["member_no"].$memberData["phone_number"].'otp:'.$otp;
			$messageResponse = 'กรุณาพิมพ์รหัสOTP เช่น otp/'.$otp;
			$dataPrepare = $lineLib->prepareMessageText($messageResponse);
			$arrPostData["messages"] = $dataPrepare;
			$arrPostData["replyToken"] = $reply_token;	
		}else{
			$arrPostData['replyToken'] = [
				':member_no' => $memberData["member_no"], 
				':otp' => $otp,
				':line_token' => $user_id
			];
		}	
	}
	/*
	//ส่ง sms
	$otp = $lib->randomText('number',6);
	$arrVerifyToken['exp'] = time() + 300;
	$arrVerifyToken['action'] = "sendmsg";
	$arrVerifyToken["mode"] = "eachmsg";
	$arrVerifyToken['typeMsg'] = 'OTP';
	$verify_token =  $jwt_token->customPayload($arrVerifyToken, "FykDK4T%bPSk");
	$arrMsg[0]["msg"] = 'รหัส OTP:235893';
	$arrMsg[0]["to"] =  $arrMessage[1];
	$arrSendData["dataMsg"] = $arrMsg;
	$arrSendData["custId"] = 'mhd';
	$arrHeader[] = "version: v1";
	$arrHeader[] = "OAuth: Bearer ".$verify_token;
	$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/navigator',$arrSendData,$arrHeader);
	if($arraySendSMS["result"]){
				
	}else{
		$messageResponse = 'ส่ง otp ไม่ได้';
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;		
	}
	*/
}else if($arrMessage[0] == 'otp'){
	$getDataMember = $conmysql->prepare("SELECT member_no,otp,confirm FROM lbotp WHERE line_token = :line_token ");
	$getDataMember->execute([
		':line_token' => $user_id
	]);
	$memberData = $getDataMember->fetch(PDO::FETCH_ASSOC);
	if($getDataMember->rowCount() > 0){
		if($arrMessage[1] == $memberData["otp"] ){
			$update_lineToken = $conmysql->prepare("UPDATE gcmemberaccount 
											SET line_token = :line_token
											WHERE  member_no = :member_no");
			if($update_lineToken->execute([
				':line_token' => $user_id, 
				':member_no' => $memberData["member_no"]
			])){
				$otp = $lib->randomText('number',6);
				$updateOtp = $conmysql->prepare("UPDATE lbotp SET confirm = :confirm WHERE member_no = :member_no" );
				if($updateOtp->execute([
					':member_no' => $memberData["member_no"], 
					':confirm' => '1'
				])){
					$messageResponse = "ผูกบัญชีสำเร็จ";
					$dataPrepare = $lineLib->prepareMessageText($messageResponse);
					$arrPostData["messages"] = $dataPrepare;
					$arrPostData["replyToken"] = $reply_token;
				}else{
					$arrPostData['replyToken'] = $updateOtp;
				}
				
			}else{
				$messageResponse = "ไม่สามารถผูกบัญชีได้";
				$dataPrepare = $lineLib->prepareMessageText($messageResponse);
				$arrPostData["messages"] = $dataPrepare;
				$arrPostData["replyToken"] = $reply_token;
			}
		}else{
			$messageResponse = 'OTP ไม่ถูกต้อง';
			$dataPrepare = $lineLib->prepareMessageText($messageResponse);
			$arrPostData["messages"] = $dataPrepare;
			$arrPostData["replyToken"] = $reply_token;	
		}
	}else{
		$messageResponse = 'ไม่พบข้อมูลการผูกบัญชี กรุณาพิมพ์ เลขสมาชิก/เบอร์โทร เช่น 123456/0992508181';
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;	
	}
}else{
	require_once('./service/notrespondmessage.php');
}
?>