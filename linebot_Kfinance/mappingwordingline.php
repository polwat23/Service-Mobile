<?php
$arrMessage = explode("/",$message);
$checkBeenBind = $conmysql->prepare("SELECT id_card,otp FROM kfinancelinememberaccount WHERE line_token = :line_id AND confirm_otp ='1'");
$checkBeenBind->execute([':line_id' => $user_id]);	
	
$getMeberData = $conmysql->prepare("SELECT id_card,otp,confirm_otp,tel FROM kfinancelinememberaccount WHERE line_token = :line_id");
$getMeberData->execute([':line_id' => $user_id]);	
$meberData = $getMeberData->fetch(PDO::FETCH_ASSOC);
$otp = 	$meberData["otp"]??'';
$confirmOtp = 	$meberData["confirm_otp"]??'';
$memberIdCard = $meberData["id_card"]??'';
$memberPhone = $meberData["tel"]??'';
	
if(sizeof($arrMessage) == 2){
	if($arrMessage[0] == 'otp' || $arrMessage[0] == 'Otp' || $arrMessage[0] == 'OTp' || $arrMessage[0] == 'OTP'){
		require_once('./service/confirm_otp.php');
	}else{
		require_once('./service/bindaccoun.php');
	}
	
//sent	otp
	/*
	if($arrMessage[0]== "1122115010888" && $arrMessage[1]== "0992409191"){
		$data = "กรุณากรอกพิมพ์ รหัส OTP";
		$dataTemplate = $lineLib->mergeTextMessage($data);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['action'] = "sendmsg";
		$arrVerifyToken["mode"] = "eachmsg";
		$arrVerifyToken['typeMsg'] = 'OTP';
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, "FykDK4T%bPSk");
		$arrMsg[0]["msg"] = 'รหัส OTP: 888899';
		$arrMsg[0]["to"] =  $arrMessage[1];
		$arrSendData["dataMsg"] = $arrMsg;
		$arrSendData["custId"] = 'mhd';
		$arrHeader[] = "version: v1";
		$arrHeader[] = "OAuth: Bearer ".$verify_token;
		$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/navigator',$arrSendData,$arrHeader);
	
	}else{
		$text = "ไม่พบข้อมูลของท่านกรุณาติดต่อเจ้าหน้าที่";
		$dataTemplate = $lineLib->mergeTextMessage($text);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}
	*/
}else{
	if($message == "ข้อมูลของฉัน"){
		$dataTemplate = $lineLib->meberInfor();
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}else if($message == "ช่วยเหลือ"){
		$helpMessage = "ท่านสามารถ เลือกรายการ จากเมนูได้";
		$dataTemplate = $lineLib->mergeTextMessage($helpMessage);
		//$dataTemplate = $lineLib->meberInfor();
		$arrPostData['messages'][0] = $dataTemplate;
	}else if($message == "ประวัติชำระหนี้"){
		require_once('./service/credit_statment.php');
	}else if($message =="ใบเสร็จ"){
		require_once('./service/credit_statment.php');
	}else if($message =="ดูรายการทั้งหมด"){
		$dataTemplate = $lineLib->flexRecept();
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}else if($message =="location"){
		$title ="บริษัท เจน ซอฟท์ จำกัด";
		$address = "219/14 หมู่ 8 ถนนวงแหวนรอบกลาง San Phi Suea, Mueang Chiang Mai District, Chiang Mai 50300";
		$latitude = "18.8272697";
		$longitude = "99.0020655";
		$dataTemplate = $lineLib->mergeLocationMessage($title,$address,$latitude,$longitude);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}else if($message == "ติดต่อเจ้าหน้าที่"){
		$data = "กรุณารอสักครู่  รอเจ้าหน้าที่ติดต่อกลับ";
		$dataTemplate = $lineLib->mergeTextMessage($data);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}else if($message == "จัดการบัญชี"){
		require_once('./service/manageaccount.php');
	}else if($message == "ข้อมูลสินเชื่อ"){
		require_once('./service/credit.php');
	}else if($message == $otp){
		$arrMessage[1] = $message;
		require_once('./service/confirm_otp.php');
	}else if($message == "ยกเลิกผูกบัญชี"){
		require_once('./service/unbindaccoun.php');
	}else if($message == "otp_test"){
		require_once('./service/send_otp.php');
	}else{
		//	$data = "ไม่พบข้อมูล  กรุณาเลือกเมนู";
		//	$dataTemplate = $lineLib->mergeTextMessage($data);
		//	$arrPostData['messages'][0] = $dataTemplate;
		//	$arrPostData['replyToken'] = $reply_token;
	}
}
require_once(__DIR__.'./replyresponse.php');
?>