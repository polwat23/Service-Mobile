<?php

if($lineLib->checkBindAccount($user_id)){

	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	
	$fetchUserAccount = $conmysql->prepare("SELECT account_status
											FROM gcmemberaccount WHERE line_token = :line_token ");
	$fetchUserAccount->execute([
		':line_token' => $user_id
	]);
	 $userData = $fetchUserAccount->fetch(PDO::FETCH_ASSOC);
	 $account_status = $userData["account_status"];
	
	
	 if($account_status == '-8'){
		$messageResponse = 'บัญชีของคุณ ถูกล็อกไปแล้ว หากต้องการใช้งานกรุณาติดต่อสหกรณ์';
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
		 
		
	 }else{
		$datas = [];
		$datas["type"] = "flex";
		$datas["altText"] = "ยืนยันการล็อคบัญชี";
		$datas["contents"]["type"] = "bubble";
		$datas["contents"]["body"]["type"] = "box";
		$datas["contents"]["body"]["layout"] = "vertical";
		$datas["contents"]["body"]["contents"][0]["type"] = "image";
		$datas["contents"]["body"]["contents"][0]["url"] = "https://cdn.thaicoop.co/icon/warning.png";
		$datas["contents"]["body"]["contents"][1]["type"] = "text";
		$datas["contents"]["body"]["contents"][1]["text"] = "คุณต้องการ ล็อคบัญชี ใช่หรือไม่";
		$datas["contents"]["body"]["contents"][1]["align"] = "center";
		$datas["contents"]["body"]["contents"][2]["type"] = "text";
		$datas["contents"]["body"]["contents"][2]["text"] = "หากบัญชีของคุณถูกล็อค คุณจะไม่สามารถเข้าสู่ระบบ RYT Saving ได้อีก ต้องไปปลดล็อคที่สหกรณ์เท่านนั้น";
		$datas["contents"]["body"]["contents"][2]["size"] = "sm";
		$datas["contents"]["body"]["contents"][2]["wrap"] = true;
		$datas["contents"]["body"]["contents"][2]["color"] = "#ee404c";
		$datas["contents"]["footer"]["type"] = "box";
		$datas["contents"]["footer"]["layout"] = "horizontal";
		$datas["contents"]["footer"]["contents"][0]["type"] = "button";
		$datas["contents"]["footer"]["contents"][0]["action"]["type"] = "message";
		$datas["contents"]["footer"]["contents"][0]["action"]["label"] = "ล็อคบัญชี";
		$datas["contents"]["footer"]["contents"][0]["action"]["text"] = "#ยืนยันการล็อคบัญชี";
		$datas["contents"]["footer"]["contents"][0]["color"] = "#ee404c";
		$datas["contents"]["footer"]["contents"][0]["style"] = "primary"; 
		$arrPostData["messages"][0] = $datas;
		$arrPostData["replyToken"] = $reply_token; 
	 }
	


	/*
	$queryString = 'UPDATE gcmemberaccount SET prev_acc_status = account_status,account_status = :account_status,counter_wrongpass = 0
					WHERE member_no = :member_no';

	$updateStatus = $conmysql->prepare($queryString);
	if($updateStatus->execute([
		':account_status' => $dataComing["account_status"],
		':member_no' => $dataComing["member_no"]
	])){
		$altText = "ล็อคบัญสำเร็จ หากต้องการใช้งานให้ติดต่อสหกรร์";
		$dataMs = $lineLib->notBindAccount();
		$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
		
	}
	*/
	
}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
	

?>