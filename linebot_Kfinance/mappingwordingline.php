<?php

	$arrPostData['replyToken'] = $reply_token;
	
	$arrMessage = explode("/",$message);
	
	if(sizeof($arrMessage) == 2){
		//$message_data = $arrMessage[1]; 
		//$dataTemplate = $lineLib->mergeTextMessage($message_data);
		//$arrPostData['messages'][0] = $dataTemplate;
		
		$dataOTP = "กรุณากรอกรหัส otp";
		$dataTemplate = $lineLib->mergeTextMessage($dataOTP);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['action'] = "sendmsg";
		$arrVerifyToken["mode"] = "eachmsg";
		$arrVerifyToken['typeMsg'] = 'OTP';
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, "FykDK4T%bPSk");
		$arrMsg[0]["msg"] = 'รหัส OTP: 888999';
		$arrMsg[0]["to"] =  $arrMessage[1];
		$arrSendData["dataMsg"] = $arrMsg;
		$arrSendData["custId"] = 'mhd';
		$arrHeader[] = "version: v1";
		$arrHeader[] = "OAuth: Bearer ".$verify_token;
		$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/navigator',$arrSendData,$arrHeader);
		
	}else{
		if($message == "888999"){
			//$dataPDF = "https://cdn.thaicoop.co/gensoft/%E0%B8%82%E0%B9%89%E0%B8%AD%E0%B8%A1%E0%B8%B9%E0%B8%A5.pdf";
			//$dataTemplate = $lineLib->mergeTextMessage($dataPDF);
			//$dataTemplate = $lineLib->flexExData();
			//$arrPostData['messages'][0] = $dataTemplate;
			$dataTemplate = $lineLib->flexRecept();
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($message == "ข้อมูลของฉัน"){
			$dataTemplate = $lineLib->meberInfor();
			$arrPostData['messages'][0] = $dataTemplate;

		}else if($message == "ช่วยเหลือ"){
			$helpMessage = "ท่านสามารถ เลือกรายการ จากเมนูได้";
			$dataTemplate = $lineLib->mergeTextMessage($helpMessage);
			
			//$dataTemplate = $lineLib->meberInfor();
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($message =="ใบเสร็จ"){
			$data = "กรูณากรอก รหัสประชาชน/เบอร์โทร  เช่น 1245784512457/0992408181";
			$dataTemplate = $lineLib->mergeTextMessage($data);
			$arrPostData['messages'][0] = $dataTemplate;
		
		}else if($message =="รายละเอียดใบเสร็จ"){
			
			$dataTemplate = $lineLib->flexExData();
			$arrPostData['messages'][0] = $dataTemplate;
		
		}else if($message =="location"){
			$title ="บริษัท เจน ซอฟท์ จำกัด";
			$address = "219/14 หมู่ 8 ถนนวงแหวนรอบกลาง San Phi Suea, Mueang Chiang Mai District, Chiang Mai 50300";
			$latitude = "18.8272697";
			$longitude = "99.0020655";
			
			$dataTemplate = $lineLib->mergeLocationMessage($title,$address,$latitude,$longitude);
			$arrPostData['messages'][0] = $dataTemplate;
		
		}else if($message == "ติดต่อเจ้าหน้าที่"){
				$data = "กรุณารอสักครู่  รอเจ้าหน้าที่ติดต่อกลับ";
				$dataTemplate = $lineLib->mergeTextMessage($data);
				$arrPostData['messages'][0] = $dataTemplate;
		}else{
			$data = "ไม่พบข้อมูล  กรุณาเลือกเมนู";
			$dataTemplate = $lineLib->mergeTextMessage($data);
			$arrPostData['messages'][0] = $dataTemplate;
		}
	}
	

	
	
	
	
	


	require_once(__DIR__.'./replyresponse.php');
?>