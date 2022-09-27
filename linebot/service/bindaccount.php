<?php
use Endroid\QrCode\QrCode;
//เช็ค 

$checkBeenBind = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE line_token = :line_id");
$themeColor = $lineLib->getLineConstant('theme_color');
$checkBeenBind->execute([':line_id' => $user_id]);
if($checkBeenBind->rowCount() > 0){
	$datasConten = array();
	$altText = "จัดการบัญชี";
	$dataContent["type"] = "bubble";
	$dataContent["body"]["type"] = "box";
	$dataContent["body"]["layout"] = "vertical";
	$dataContent["body"]["contents"][0]["type"] = "text";
	$dataContent["body"]["contents"][0]["text"] = "ท่านผูกบัญชีอยู่แล้ว";
	$dataContent["body"]["contents"][0]["color"] = "#0EA7CA";
	$dataContent["body"]["contents"][1]["type"] = "text";
	$dataContent["body"]["contents"][1]["text"] = " หากท่านต้องการเปลี่ยนบัญชีให้กดปุ่ม ";
	$dataContent["body"]["contents"][1]["size"] = "sm";
	$dataContent["body"]["contents"][1]["wrap"] = true;
	$dataContent["body"]["contents"][1]["offsetStart"] = "40px";
	$dataContent["body"]["contents"][2]["type"] = "text";
	$dataContent["body"]["contents"][2]["text"] = "ยกเลิกผูกบัญชี";
	$dataContent["body"]["contents"][2]["size"] = "sm";
	$dataContent["body"]["contents"][2]["weight"] = "bold";
	$dataContent["body"]["contents"][2]["color"] = $themeColor;
	$dataContent["body"]["contents"][3]["type"] = "button";
	$dataContent["body"]["contents"][3]["action"]["type"] = "message";
	$dataContent["body"]["contents"][3]["action"]["label"] = "ยกเลิกผูกบัญชี";
	$dataContent["body"]["contents"][3]["action"]["text"] = "ยกเลิกผูกบัญชี";
	$dataContent["body"]["contents"][3]["height"] = "sm";
	$dataContent["body"]["contents"][3]["style"] = "primary";
	$dataContent["body"]["contents"][3]["margin"] = "xl";
	$dataContent["body"]["contents"][3]["color"] = $themeColor;
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataContent);
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

	$altText = "จัดการบัญชี";
	$dataContent = array();
	$dataContent["type"] = "carousel";
	$dataContent["contents"][0]["type"] = "bubble";
	$dataContent["contents"][0]["direction"] = "ltr";
	$dataContent["contents"][0]["header"]["type"] = "box";
	$dataContent["contents"][0]["header"]["layout"] = "vertical";
	$dataContent["contents"][0]["header"]["contents"][0]["type"] = "text";
	$dataContent["contents"][0]["header"]["contents"][0]["text"] = "ผูกบัญชีกับ ".$configLine["LINEBOT_NAME"];
	$dataContent["contents"][0]["header"]["contents"][0]["weight"] = "bold";
	$dataContent["contents"][0]["header"]["contents"][0]["size"] = "md";
	$dataContent["contents"][0]["header"]["contents"][0]["color"] = "#000000FF";
	$dataContent["contents"][0]["header"]["contents"][0]["align"] = "center";
	$dataContent["contents"][0]["header"]["contents"][0]["wrap"] = true;
	$dataContent["contents"][0]["type"] = "bubble";
	$dataContent["contents"][0]["direction"] = "ltr";
	$dataContent["contents"][0]["hero"]["type"] = "image";
	$dataContent["contents"][0]["hero"]["url"] = $fullPath;
	$dataContent["contents"][0]["hero"]["size"] = "4xl";
	$dataContent["contents"][0]["hero"]["aspectRatio"] = "1.51:1";
	$dataContent["contents"][0]["hero"]["aspectMode"] = "fit";
	$dataContent["contents"][0]["body"]["type"] = "box";
	$dataContent["contents"][0]["body"]["layout"] = "vertical";
	$dataContent["contents"][0]["body"]["contents"][0]["type"] = "text";
	$dataContent["contents"][0]["body"]["contents"][0]["text"] = "หากใช้งาน Line บนคอมพิวเตอร์ให้ใช้ แอปพลิเคชัน RYT Saving เข้าเมนูสแกน QR Code แล้วสแกน";
	$dataContent["contents"][0]["body"]["contents"][0]["size"] = "sm";
	$dataContent["contents"][0]["body"]["contents"][0]["color"] = "#000000FF";
	$dataContent["contents"][0]["body"]["contents"][0]["wrap"] = true;
	$dataContent["contents"][0]["body"]["contents"][1]["type"] = "text";
	$dataContent["contents"][0]["body"]["contents"][1]["text"] = "QR Code หมดอายุ ".$lib->convertdate($expireDate,'D m Y',true);
	$dataContent["contents"][0]["body"]["contents"][1]["size"] = "xs";
	$dataContent["contents"][0]["body"]["contents"][1]["weight"] = "bold";
	$dataContent["contents"][0]["body"]["contents"][1]["color"] = "#FF0000FF";
	$dataContent["contents"][0]["body"]["contents"][1]["align"] = "center";
	$dataContent["contents"][0]["body"]["contents"][1]["margin"] = "none";
	$dataContent["contents"][0]["body"]["contents"][1]["offsetTop"] = "5%";
	$dataContent["contents"][0]["body"]["contents"][1]["offsetBottom"] = "5%";
	$dataContent["contents"][0]["body"]["contents"][2]["type"] = "button";
	$dataContent["contents"][0]["body"]["contents"][2]["action"]["type"] = "uri";
	$dataContent["contents"][0]["body"]["contents"][2]["action"]["label"] = "ใช้งานผ่านมือถือ";
	$dataContent["contents"][0]["body"]["contents"][2]["action"]["uri"] = "https://liff.line.me/".$configLine["LIFF_ID"]."?id=".$user_id;
	$dataContent["contents"][0]["body"]["contents"][2]["color"] = $themeColor;
	$dataContent["contents"][0]["body"]["contents"][2]["margin"] = "lg";
	$dataContent["contents"][0]["body"]["contents"][2]["height"] = "sm";
	$dataContent["contents"][0]["body"]["contents"][2]["style"] = "primary";
	$dataContent["contents"][0]["body"]["contents"][2]["offsetTop"] = "10%";
	$dataContent["contents"][0]["body"]["contents"][3]["type"] = "spacer";
	
	/*
	$dataContent["contents"][1]["type"] = "bubble";
	$dataContent["contents"][1]["direction"] = "ltr";
	$dataContent["contents"][1]["body"]["type"] = "box";
	$dataContent["contents"][1]["body"]["layout"] = "vertical";
	$dataContent["contents"][1]["body"]["contents"][0]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][0]["text"] = "ผูกโดยใช้ OTP";
	$dataContent["contents"][1]["body"]["contents"][0]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][0]["align"] = "center";
	$dataContent["contents"][1]["body"]["contents"][1]["type"] = "box";
	$dataContent["contents"][1]["body"]["contents"][1]["layout"] = "vertical";
	$dataContent["contents"][1]["body"]["contents"][1]["justifyContent"] = "center";
	$dataContent["contents"][1]["body"]["contents"][1]["margin"] = "md";
	$dataContent["contents"][1]["body"]["contents"][1]["offsetStart"] = "20px";
	$dataContent["contents"][1]["body"]["contents"][1]["paddingAll"] = "5px";
	$dataContent["contents"][1]["body"]["contents"][1]["width"] = "250px";
	$dataContent["contents"][1]["body"]["contents"][1]["borderWidth"] = "1px";
	$dataContent["contents"][1]["body"]["contents"][1]["borderColor"] = "#E3519D";
	$dataContent["contents"][1]["body"]["contents"][1]["cornerRadius"] = "10px";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["text"] = "text";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["size"] = "xs";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["align"] = "start";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["margin"] = "md";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["wrap"] = true;
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["contents"][0]["type"] = "span";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["contents"][0]["text"] = "กรุณาพิมพ์  ";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["contents"][1]["type"] = "span";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["contents"][1]["text"] = "เลขสมาชิก/เบอร์โทรศัพท์";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][0]["contents"][1]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["text"] = "text";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["size"] = "xs";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][0]["type"] = "span";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][0]["text"] = "ตัวอย่าง ";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][0]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][1]["type"] = "span";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][1]["text"] = "999999/0992308484";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][1]["color"] = "#142DD7FF";
	$dataContent["contents"][1]["body"]["contents"][1]["contents"][1]["contents"][1]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][2]["type"] = "box";
	$dataContent["contents"][1]["body"]["contents"][2]["layout"] = "vertical";
	$dataContent["contents"][1]["body"]["contents"][2]["justifyContent"] = "center";
	$dataContent["contents"][1]["body"]["contents"][2]["margin"] = "md";
	$dataContent["contents"][1]["body"]["contents"][2]["paddingAll"] = "5px";
	$dataContent["contents"][1]["body"]["contents"][2]["width"] = "230px";
	$dataContent["contents"][1]["body"]["contents"][2]["borderWidth"] = "1px";
	$dataContent["contents"][1]["body"]["contents"][2]["borderColor"] = "#E3519D";
	$dataContent["contents"][1]["body"]["contents"][2]["cornerRadius"] = "10px";
	$dataContent["contents"][1]["body"]["contents"][2]["contents"][0]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][2]["contents"][0]["text"] = "รับ รหัส OTP  ";
	$dataContent["contents"][1]["body"]["contents"][2]["contents"][0]["size"] = "xs";
	$dataContent["contents"][1]["body"]["contents"][2]["contents"][0]["align"] = "start";
	$dataContent["contents"][1]["body"]["contents"][2]["contents"][0]["margin"] = "md";
	$dataContent["contents"][1]["body"]["contents"][2]["contents"][0]["wrap"] = true;
	$dataContent["contents"][1]["body"]["contents"][3]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][3]["text"] = "1";
	$dataContent["contents"][1]["body"]["contents"][3]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][3]["size"] = "xxl";
	$dataContent["contents"][1]["body"]["contents"][3]["color"] = "#FF0000";
	$dataContent["contents"][1]["body"]["contents"][3]["position"] = "absolute";
	$dataContent["contents"][1]["body"]["contents"][3]["offsetTop"] = "50px";
	$dataContent["contents"][1]["body"]["contents"][3]["offsetStart"] = "10px";
	$dataContent["contents"][1]["body"]["contents"][4]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][4]["text"] = "2";
	$dataContent["contents"][1]["body"]["contents"][4]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][4]["size"] = "xxl";
	$dataContent["contents"][1]["body"]["contents"][4]["color"] = "#FF0000";
	$dataContent["contents"][1]["body"]["contents"][4]["position"] = "absolute";
	$dataContent["contents"][1]["body"]["contents"][4]["offsetTop"] = "110px";
	$dataContent["contents"][1]["body"]["contents"][4]["offsetEnd"] = "20px";
	$dataContent["contents"][1]["body"]["contents"][5]["type"] = "box";
	$dataContent["contents"][1]["body"]["contents"][5]["layout"] = "vertical";
	$dataContent["contents"][1]["body"]["contents"][5]["margin"] = "md";
	$dataContent["contents"][1]["body"]["contents"][5]["justifyContent"] = "center";
	$dataContent["contents"][1]["body"]["contents"][5]["offsetStart"] = "20px";
	$dataContent["contents"][1]["body"]["contents"][5]["paddingAll"] = "5px";
	$dataContent["contents"][1]["body"]["contents"][5]["width"] = "250px";
	$dataContent["contents"][1]["body"]["contents"][5]["borderWidth"] = "1px";
	$dataContent["contents"][1]["body"]["contents"][5]["borderColor"] = "#E3519D";
	$dataContent["contents"][1]["body"]["contents"][5]["cornerRadius"] = "10px";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][0]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][0]["text"] = "จากนั้นยืนยันรหัส otpโดย พิมพ์ otp/รหัส otp";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][0]["size"] = "xs";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][0]["align"] = "start";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][0]["margin"] = "md";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][0]["wrap"] = true;
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["text"] = "text";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["size"] = "xs";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][0]["type"] = "span";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][0]["text"] = "ตัวอย่าง ";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][0]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][1]["type"] = "span";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][1]["text"] = "otp/999999";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][1]["color"] = "#D77432FF";
	$dataContent["contents"][1]["body"]["contents"][5]["contents"][1]["contents"][1]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][6]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][6]["text"] = "3";
	$dataContent["contents"][1]["body"]["contents"][6]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][6]["size"] = "xxl";
	$dataContent["contents"][1]["body"]["contents"][6]["color"] = "#FF0000";
	$dataContent["contents"][1]["body"]["contents"][6]["position"] = "absolute";
	$dataContent["contents"][1]["body"]["contents"][6]["offsetTop"] = "170px";
	$dataContent["contents"][1]["body"]["contents"][6]["offsetStart"] = "10px";
	$dataContent["contents"][1]["body"]["contents"][7]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][7]["text"] = "**กรณีไม่เคยสมัคใช้งาน NKBKConnext";
	$dataContent["contents"][1]["body"]["contents"][7]["weight"] = "bold";
	$dataContent["contents"][1]["body"]["contents"][7]["size"] = "xs";
	$dataContent["contents"][1]["body"]["contents"][7]["color"] = "#FF0000";
	$dataContent["contents"][1]["body"]["contents"][7]["margin"] = "xxl";
	$dataContent["contents"][1]["body"]["contents"][8]["type"] = "text";
	$dataContent["contents"][1]["body"]["contents"][8]["text"] = "สามารถดาวน์โหลดและสมัครใช้ได้ที่";
	$dataContent["contents"][1]["body"]["contents"][8]["size"] = "sm";
	$dataContent["contents"][1]["body"]["contents"][9]["type"] = "box";
	$dataContent["contents"][1]["body"]["contents"][9]["layout"] = "horizontal";
	$dataContent["contents"][1]["body"]["contents"][9]["height"] = "100px";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][0]["type"] = "image";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][0]["url"] = "https://cdn.thaicoop.co/store/appStore.png";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][0]["size"] = "lg";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][0]["action"]["type"] = "uri";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][0]["action"]["label"] = "label";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][0]["action"]["uri"] = "https://apps.apple.com/th/app/nkbkconnext/id1554206325";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][1]["type"] = "image";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][1]["url"] = "https://cdn.thaicoop.co/store/googlePlay.png";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][1]["size"] = "lg";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][1]["action"]["type"] = "uri";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][1]["action"]["label"] = "label";
	$dataContent["contents"][1]["body"]["contents"][9]["contents"][1]["action"]["uri"] = "https://play.google.com/store/apps/details?id=com.nkhsaving.mobile";
*/

	
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataContent);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>