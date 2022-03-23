<?php  
$checkBeenBind = $conmysql->prepare("SELECT id_card,otp,confirm_otp FROM kfinancelinememberaccount WHERE line_token = :line_id");
$checkBeenBind->execute([':line_id' => $user_id]);
if($checkBeenBind->rowCount() > 0){

	$groupData = $checkBeenBind->fetch(PDO::FETCH_ASSOC);
	//$arrPostData['replyToken'] = $groupData;
	if($groupData["confirm_otp"]=='1'){
		$datas = [];
		$datas["type"] = "flex";
		$datas["altText"] = "จัดการบัญชี";
		$datas["contents"]["type"] = "bubble";
		$datas["contents"]["direction"] = "ltr";
		$datas["contents"]["body"]["type"] = "box";
		$datas["contents"]["body"]["layout"] = "vertical";
		$datas["contents"]["body"]["contents"][0]["type"] = "text";
		$datas["contents"]["body"]["contents"][0]["text"] = "ท่านได้ผูกบัญชีสำเร็จแล้ว";
		$datas["contents"]["body"]["contents"][0]["weight"] = "regular";
		$datas["contents"]["body"]["contents"][0]["size"] = "lg";
		$datas["contents"]["body"]["contents"][0]["color"] = "#312DD4FF";
		$datas["contents"]["body"]["contents"][0]["align"] = "center";
		$datas["contents"]["body"]["contents"][1]["type"] = "box";
		$datas["contents"]["body"]["contents"][1]["layout"] = "vertical";
		$datas["contents"]["body"]["contents"][1]["margin"] = "xl";
		$datas["contents"]["body"]["contents"][1]["contents"][0]["type"] = "button";
		$datas["contents"]["body"]["contents"][1]["contents"][0]["action"]["type"] = "message";
		$datas["contents"]["body"]["contents"][1]["contents"][0]["action"]["label"] = "ยกเลิกผูกบัญชี";
		$datas["contents"]["body"]["contents"][1]["contents"][0]["action"]["text"] = "ยกเลิกผูกบัญชี";
		$datas["contents"]["body"]["contents"][1]["contents"][0]["color"] = "#E21212FF";
		$datas["contents"]["body"]["contents"][1]["contents"][0]["style"] = "primary";
	
		$arrPostData['messages'][0] = $datas;
		$arrPostData['replyToken'] = $reply_token;
	
	}else{
		$data = "ท่านยังไม่ได้ยืนยัน  รหัส OTP กรุณาพิมพ์ 'otp/รหัสotp' เช่น: otp/123456";
		$dataTemplate = $lineLib->mergeTextMessage($data);
		$arrPostData['messages'][0] = $dataTemplate;
		$arrPostData['replyToken'] = $reply_token;
	}
}else{
	$data = "ท่านยังไม่ได้ผูกบัญชีเพื่อดูข้อมูล กรุณาพิมพ์ รหัสประชาชน/เบอร์โทร  เช่น 1245784512457/0992408181";
	$dataTemplate = $lineLib->mergeTextMessage($data);
	$arrPostData['messages'][0] = $dataTemplate;
	$arrPostData['replyToken'] = $reply_token;
}
?>