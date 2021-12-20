<?php
use Endroid\QrCode\QrCode;

$checkBeenBind = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE line_token = :line_id");
$checkBeenBind->execute([':line_id' => $user_id]);
if($checkBeenBind->rowCount() > 0){
	$messageResponse = "ท่านผูกบัญชีอยู่แล้ว หากต้องการเปลี่ยนบัญชีให้พิมพ์ ' ยกเลิกผูกบัญชี '";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}else{
	$currentDate = date_create();
	$tempExpire = new DateTime(date_format($currentDate,"Y-m-d H:i:s"));
	$expireDate = $tempExpire->add(new DateInterval('PT15M'));
	$expireDate = date_format($expireDate,"Y-m-d H:i:s");
	$arrObjectQR = array();
	$arrObjectQR["type"] = "linebotregister";
	$arrObjectQR["line_id"] = $user_id;
	$arrObjectQR["expire_date"] = $expireDate;
	$stringQRGenerate = json_encode($arrObjectQR);
	$qrCode = new QrCode($stringQRGenerate);
	$randQrRef = date_format($currentDate,"YmdHis").rand(1000,9999);
	header('Content-Type: '.$qrCode->getContentType());
	$qrCode->writeString();
	$qrCode->writeFile(__DIR__.'/../../resource/qrcode/'.$user_id.$randQrRef.'.png');
	$fullPath = $config["URL_SERVICE"].'/resource/qrcode/'.$user_id.$randQrRef.'.png';
	header('Content-Type: application/json;charset=utf-8');

	$altText = "ผูกบัญชี";
	$dataContent = array();
	$dataContent["type"] = "bubble";
	$dataContent["direction"] = "ltr";
	$dataContent["header"]["type"] = "box";
	$dataContent["header"]["layout"] = "vertical";
	$dataContent["header"]["contents"][0]["type"] = "text";
	$dataContent["header"]["contents"][0]["text"] = "ผูกบัญชีกับ NKBKConnext";
	$dataContent["header"]["contents"][0]["weight"] = "bold";
	$dataContent["header"]["contents"][0]["size"] = "md";
	$dataContent["header"]["contents"][0]["color"] = "#000000FF";
	$dataContent["header"]["contents"][0]["align"] = "center";
	$dataContent["header"]["contents"][0]["wrap"] = true;
	$dataContent["type"] = "bubble";
	$dataContent["direction"] = "ltr";
	$dataContent["hero"]["type"] = "image";
	$dataContent["hero"]["url"] = $fullPath;
	$dataContent["hero"]["size"] = "4xl";
	$dataContent["hero"]["aspectRatio"] = "1.51:1";
	$dataContent["hero"]["aspectMode"] = "fit";
	$dataContent["body"]["type"] = "box";
	$dataContent["body"]["layout"] = "vertical";
	$dataContent["body"]["contents"][0]["type"] = "text";
	$dataContent["body"]["contents"][0]["text"] = "หากใช้งาน Line บนคอมพิวเตอร์ให้ใช้ แอปพลิเคชัน NKBKConnext เข้าเมนูสแกน QR Code แล้วสแกน";
	$dataContent["body"]["contents"][0]["size"] = "sm";
	$dataContent["body"]["contents"][0]["color"] = "#000000FF";
	$dataContent["body"]["contents"][0]["wrap"] = true;
	$dataContent["body"]["contents"][1]["type"] = "text";
	$dataContent["body"]["contents"][1]["text"] = "QR Code หมดอายุ ".$lib->convertdate($expireDate,'D m Y',true);
	$dataContent["body"]["contents"][1]["size"] = "xs";
	$dataContent["body"]["contents"][1]["weight"] = "bold";
	$dataContent["body"]["contents"][1]["color"] = "#FF0000FF";
	$dataContent["body"]["contents"][1]["align"] = "center";
	$dataContent["body"]["contents"][1]["margin"] = "none";
	$dataContent["body"]["contents"][1]["offsetTop"] = "5%";
	$dataContent["body"]["contents"][1]["offsetBottom"] = "5%";
	$dataContent["body"]["contents"][2]["type"] = "button";
	$dataContent["body"]["contents"][2]["action"]["type"] = "uri";
	$dataContent["body"]["contents"][2]["action"]["label"] = "ใช้งานผ่านมือถือ";
	$dataContent["body"]["contents"][2]["action"]["uri"] = "https://liff.line.me/1656729415-nDx8JYBP?page=register";
	$dataContent["body"]["contents"][2]["color"] = "#E3519DFF";
	$dataContent["body"]["contents"][2]["margin"] = "lg";
	$dataContent["body"]["contents"][2]["height"] = "sm";
	$dataContent["body"]["contents"][2]["style"] = "primary";
	$dataContent["body"]["contents"][2]["offsetTop"] = "10%";
	$dataContent["body"]["contents"][3]["type"] = "spacer";
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataContent);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>