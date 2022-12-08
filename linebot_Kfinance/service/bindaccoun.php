<?php 
if($checkBeenBind->rowCount() > 0){
	$data = "ท่านได้ผูกบัญชีแล้ว สามารถดูข้อมูลได้แล้ว";
	$data = $memberData;
	$dataTemplate = $lineLib->mergeTextMessage($data);
	$arrPostData['messages'][0] = $dataTemplate;
	$arrPostData['replyToken'] = $reply_token;
}else{
	//call API
	$url = 'http://www.tunthavorn-plus.com/line/api/line_service.php?idCard='.$arrMessage[0].'&mobile='.$arrMessage[1].'&topic=1';
	$data["member_info"]= $message;
	$memberInfo = $lineLib->sendApiToServer($data,$url);
	//data from API 
	$arrMember = json_decode($memberInfo["message"],true);
	$rowData= $arrMember[0];
	$mobile = $rowData["mobile"]??'null';
	$msg = $rowData["msg"]??'null';
	$resultCode = $rowData["resultCode"]??'null';
	$idCard = $rowData["idCard"]??'null';
	if($resultCode == "1"){
		$otp = $lib->randomText('number',6);
		if($confirmOtp == '0'){
			$update = $conmysql->prepare("UPDATE  kfinancelinememberaccount
												SET 
													id_card = :id_card,
													line_token = :line_token,
													otp = :otp,
													tel = :tel");
			if($update->execute([
				':id_card' => $idCard, 
				':line_token' => $user_id, 
				':otp' => $otp,
				':tel' => $mobile
			])){
				$arrVerifyToken['exp'] = time() + 300;
				$arrVerifyToken['action'] = "sendmsg";
				$arrVerifyToken["mode"] = "eachmsg";
				$arrVerifyToken['typeMsg'] = 'OTP';
				$verify_token =  $jwt_token->customPayload($arrVerifyToken, 'FykDK4T%bPSk');
				$messageOTP = 'รหัส OTP ของท่านคือ '.$otp;
				$arrMsg[0]["msg"] = $messageOTP;
				$arrMsg[0]["to"] = $mobile;
				$arrSendData["dataMsg"] = $arrMsg;
				$arrSendData["custId"] = 'mhd';
				$arrHeader[] = "version: v1";
				$arrHeader[] = "OAuth: Bearer ".$verify_token;
				$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/beta/navigator',$arrSendData,$arrHeader);
				if($arraySendSMS["result"]){
					$arrResult = json_decode($arraySendSMS,true);
					if($arrResult["result"]){
						$data = "กรุณาพิมพ์รหัส OTP เช่น  123456";
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
				}
			}else{
				$arrPostData['replyToken'] = 'update';
			}
		}else{
			$insertmeber = $conmysql->prepare("INSERT INTO kfinancelinememberaccount
												(id_card,otp,line_token,tel)
												VALUES(:id_card,:otp,:line_token,:tel)");
			if($insertmeber->execute([
				':id_card' => $idCard, 
				':line_token' => $user_id, 
				':otp' => $otp,
				':tel' => $mobile
			])){
				$arrVerifyToken['exp'] = time() + 300;
				$arrVerifyToken['action'] = "sendmsg";
				$arrVerifyToken["mode"] = "eachmsg";
				$arrVerifyToken['typeMsg'] = 'OTP';
				$verify_token =  $jwt_token->customPayload($arrVerifyToken, 'FykDK4T%bPSk');
				$messageOTP = 'รหัส OTP ของท่านคือ '.$otp;
				$arrMsg[0]["msg"] = $messageOTP;
				$arrMsg[0]["to"] = $mobile;
				$arrSendData["dataMsg"] = $arrMsg;
				$arrSendData["custId"] = 'mhd';
				$arrHeader[] = "version: v1";
				$arrHeader[] = "OAuth: Bearer ".$verify_token;
				$arraySendSMS = $lib->posting_data('https://sms.thaicoop.co/beta/navigator',$arrSendData,$arrHeader);
				if($arraySendSMS["result"]){
					$arrResult = json_decode($arraySendSMS,true);
					if($arrResult["result"]){
						$data = "กรุณาพิมพ์รหัส OTP เช่น  123456";
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
				}
			}else{
				$arrPostData['replyToken'] = [
					':member_no' => $groupData["MEMBER_NO"], 
					':line_token' => $user_id, 
					':otp' => $otp,
					':coop_control' => $groupData["COOP_CONTROL"],
					':phone' => $groupData["PHONE_NUMBER"]
				];
			} 
		}	
	}else{
		$dataTemplate = $lineLib->mergeTextMessage($msg);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}
}

?>