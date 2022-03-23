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
	$data["member_info"]= $message;//"3900900541209/0818173439";
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
				$ms1 = "รหัส OTP ของท่านคือ".$otp;
				$ms2 = "กรุณาพิมพ์  รหัส OTP เช่น otp/123456";
				$messageOTP = $lineLib->mergeTextMessage($ms1);
				$messageResponse = $lineLib->mergeTextMessage($ms2);
				$arrPostData['messages'][0] = $messageOTP;
				$arrPostData['messages'][1] = $messageResponse;
				$arrPostData['replyToken'] = $reply_token;
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
				$ms1 = "รหัส OTP ของท่านคือ ".$otp;
				$ms2 = "กรุณาพิมพ์  รหัส OTP เช่น otp/123456 ";
				$messageOTP = $lineLib->mergeTextMessage($ms1);
				$messageResponse = $lineLib->mergeTextMessage($ms2);
				$arrPostData['messages'][0] = $messageOTP;
				$arrPostData['messages'][1] = $messageResponse;
				$arrPostData['replyToken'] = $reply_token;
				
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

	
/*
 
if($checkBeenBind->rowCount() > 0){
	$data = "ท่านได้ผูกบัญชีแล้ว สามารถดูข้อมูลได้แล้ว";
	$dataTemplate = $lineLib->mergeTextMessage($data);
	$arrPostData['messages'][0] = $dataTemplate;
	$arrPostData['replyToken'] = $reply_token;
}else{	

	//cal api server
	$url = "http://103.233.193.52/line_bot/mobile_and_web-control/check_member/check_member";
	$data["member_info"]= $message;//"3900900541209/0818173439";
	$memberInfo = $lineLib->sendApiToServer($data,$url);
	$groupData = json_decode($memberInfo["DATA"],true);
	if($groupData["RESULT"] === true){
		$otp = $lib->randomText('number',6);
		//$otp = '888999';
		$checkmemberData = $conmysql->prepare("SELECT member_no,otp,confirm_otp FROM kfinancelinememberaccount WHERE line_token = :line_id");
		$checkmemberData->execute([':line_id' => $user_id]);
		$memberData = $checkmemberData->fetch(PDO::FETCH_ASSOC);
		//$arrPostData['replyToken'] = $user_id;
		if($memberData["confirm_otp"]=='0'){
			$update = $conmysql->prepare("UPDATE  kfinancelinememberaccount
												SET 
													member_no = :member_no,
													line_token = :line_token,
													otp = :otp,
													coop_control = :coop_control,
													phone = :phone");
			if($update->execute([
				':member_no' => $groupData["MEMBER_NO"], 
				':line_token' => $user_id, 
				':otp' => $otp,
				':coop_control' => $groupData["COOP_CONTROL"],
				':phone' => $groupData["PHONE_NUMBER"]
			])){
				$ms1 = "รหัส OTP ของท่านคือ".$otp;
				$ms2 = "กรุณาพิมพ์  รหัส OTP เช่น otp/123456";
				$messageOTP = $lineLib->mergeTextMessage($ms1);
				$messageResponse = $lineLib->mergeTextMessage($ms2);
				$arrPostData['messages'][0] = $messageOTP;
				$arrPostData['messages'][1] = $messageResponse;
				$arrPostData['replyToken'] = $reply_token;
			}else{
				$arrPostData['replyToken'] = 'update';
			}
		}else{
			$insertmeber = $conmysql->prepare("INSERT INTO kfinancelinememberaccount
												(member_no,otp,line_token,coop_control,phone)
												VALUES(:member_no,:otp,:line_token,:coop_control,:phone)");
			if($insertmeber->execute([
				':member_no' => $groupData["MEMBER_NO"], 
				':line_token' => $user_id, 
				':otp' => $otp,
				':coop_control' => $groupData["COOP_CONTROL"],
				':phone' => $groupData["PHONE_NUMBER"]
			])){
				$ms1 = "รหัส OTP ของท่านคือ ".$otp;
				$ms2 = "กรุณาพิมพ์  รหัส OTP เช่น otp/123456 ";
				$messageOTP = $lineLib->mergeTextMessage($ms1);
				$messageResponse = $lineLib->mergeTextMessage($ms2);
				$arrPostData['messages'][0] = $messageOTP;
				$arrPostData['messages'][1] = $messageResponse;
				$arrPostData['replyToken'] = $reply_token;
				
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
		$ms = $groupData["RESPONSE_MESSAGE"];
		$dataTemplate = $lineLib->mergeTextMessage($ms);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}
}

*/


?>