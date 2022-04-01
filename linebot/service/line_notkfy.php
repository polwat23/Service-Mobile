<?php

$notify = 0;
if($notify == 1){
	$icon = "https://cdn.thaicoop.co/icon/line_notify.png";
	$color = "#35B84B";
	$text_statust = ' เปิด';
	$btn_color = "#FF0000";
	$btn_text = " ปิด";
	
}else{
	$icon = "https://cdn.thaicoop.co/icon/line_not_notify.png";
	$color = "#FF0000";
	$text_statust = ' ปิด';
	$btn_color = "#35B84B";
	$btn_text = " เปิด";
}
$datas = [];
$datas["type"] = "flex";
$datas["altText"] = "การแจ้งเตือน";
$datas["contents"]["type"] = "bubble";
$datas["contents"]["direction"] = "ltr";
$datas["contents"]["body"]["type"] = "box";
$datas["contents"]["body"]["layout"] = "vertical";
$datas["contents"]["body"]["contents"][0]["type"] = "text";
$datas["contents"]["body"]["contents"][0]["text"] = "สถานะ";
$datas["contents"]["body"]["contents"][0]["size"] = "xl";
$datas["contents"]["body"]["contents"][0]["align"] = "start";
$datas["contents"]["body"]["contents"][0]["contents"][0]["type"] = "span";
$datas["contents"]["body"]["contents"][0]["contents"][0]["text"] = "สถานะ  : ";
$datas["contents"]["body"]["contents"][0]["contents"][1]["type"] = "span";
$datas["contents"]["body"]["contents"][0]["contents"][1]["text"] = $text_statust;
$datas["contents"]["body"]["contents"][0]["contents"][1]["color"] = $color;
$datas["contents"]["body"]["contents"][0]["contents"][1]["weight"] = "bold";
$datas["contents"]["body"]["contents"][1]["type"] = "image";
$datas["contents"]["body"]["contents"][1]["url"] =  $icon;
$datas["contents"]["body"]["contents"][2]["type"] = "box";
$datas["contents"]["body"]["contents"][2]["layout"] = "vertical";
$datas["contents"]["body"]["contents"][2]["margin"] = "md";
$datas["contents"]["body"]["contents"][2]["action"]["type"] = "message";
$datas["contents"]["body"]["contents"][2]["action"]["label"] = $btn_text."การแจ้งเตือน";
$datas["contents"]["body"]["contents"][2]["action"]["text"] = $btn_text."การแจ้งเตือน";
$datas["contents"]["body"]["contents"][2]["height"] = "40px";
$datas["contents"]["body"]["contents"][2]["borderWidth"] = "1px";
$datas["contents"]["body"]["contents"][2]["borderColor"] = $btn_color;
$datas["contents"]["body"]["contents"][2]["justifyContent"] = "center";
$datas["contents"]["body"]["contents"][2]["alignItems"] = "center";
$datas["contents"]["body"]["contents"][2]["cornerRadius"] = "10px";
$datas["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
$datas["contents"]["body"]["contents"][2]["contents"][0]["text"] = $btn_text."การแจ้งเตือน";
$datas["contents"]["body"]["contents"][2]["contents"][0]["color"] = $btn_color;
$datas["contents"]["body"]["contents"][2]["contents"][0]["align"] = "center";



if($lineLib->checkBindAccount($user_id)){
	
	
	
	$arrPostData["messages"][0] = $datas; 
	$arrPostData["replyToken"] = $reply_token;
   // $messageResponse = "ขออภัยในความไม่สะดวกขณะนี้ระบบแจงเตือนได้ปิดปรับปรุงชั่วคราว";
	//$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	//$arrPostData["messages"] = $dataPrepare;
	//$arrPostData["replyToken"] = $reply_token;
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อแจ่งเตือน";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>