<?php
$json = file_get_contents(__DIR__.'/../../config/config_linebot.json');
$config = json_decode($json,true);

$checkBeenBind = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE line_token = :line_id");
$checkBeenBind->execute([':line_id' => $user_id]);

$baseUrl = 'https://cdn.thaicoop.co/linebot/imagemap/'.$config["COOP_KEY"].'.jpg';
if($checkBeenBind->rowCount() > 0){
	$allMenu = array();
	$allMenu["type"] = "imagemap";
	$allMenu["baseUrl"] = $baseUrl.'?_ignorid';
	$allMenu["altText"] = "เมนูสหกรณ์";
	$allMenu["baseSize"]["width"] = 1040;
	$allMenu["baseSize"]["height"] = 625;
	$allMenu["actions"][0]["type"] = "message";
	$allMenu["actions"][0]["area"]["x"] = 3;
	$allMenu["actions"][0]["area"]["y"] = 2;
	$allMenu["actions"][0]["area"]["width"] = 339;
	$allMenu["actions"][0]["area"]["height"] = 205;
	$allMenu["actions"][0]["text"] = "ข้อมูลส่วนตัว";
	$allMenu["actions"][1]["type"] = "message";
	$allMenu["actions"][1]["area"]["x"] = 355;
	$allMenu["actions"][1]["area"]["y"] = 4;
	$allMenu["actions"][1]["area"]["width"] = 335;
	$allMenu["actions"][1]["area"]["height"] = 203;
	$allMenu["actions"][1]["text"] = "เงินฝาก";
	$allMenu["actions"][2]["type"] = "message";
	$allMenu["actions"][2]["area"]["x"] = 699;
	$allMenu["actions"][2]["area"]["y"] = 5;
	$allMenu["actions"][2]["area"]["width"] = 337;
	$allMenu["actions"][2]["area"]["height"] = 202;
	$allMenu["actions"][2]["text"] = "เงินกู้";
	$allMenu["actions"][3]["type"] = "message";
	$allMenu["actions"][3]["area"]["x"] = 5;
	$allMenu["actions"][3]["area"]["y"] = 213;
	$allMenu["actions"][3]["area"]["width"] = 338;
	$allMenu["actions"][3]["area"]["height"] = 196;
	$allMenu["actions"][3]["text"] = "หุ้น";
	$allMenu["actions"][4]["type"] = "message";
	$allMenu["actions"][4]["area"]["x"] = 353;
	$allMenu["actions"][4]["area"]["y"] = 210;
	$allMenu["actions"][4]["area"]["width"] = 334;
	$allMenu["actions"][4]["area"]["height"] = 204;
	$allMenu["actions"][4]["text"] = "ค้ำประกัน";
	$allMenu["actions"][5]["type"] = "message";
	$allMenu["actions"][5]["area"]["x"] = 701;
	$allMenu["actions"][5]["area"]["y"] = 214;
	$allMenu["actions"][5]["area"]["width"] = 335;
	$allMenu["actions"][5]["area"]["height"] = 199;
	$allMenu["actions"][5]["text"] = "เรียกเก็บประจำเดือน";
	$allMenu["actions"][6]["type"] = "message";
	$allMenu["actions"][6]["area"]["x"] = 4;
	$allMenu["actions"][6]["area"]["y"] = 418;
	$allMenu["actions"][6]["area"]["width"] = 339;
	$allMenu["actions"][6]["area"]["height"] = 204;
	$allMenu["actions"][6]["text"] = "ปันผล";
	$allMenu["actions"][7]["type"] = "message";
	$allMenu["actions"][7]["area"]["x"] = 358;
	$allMenu["actions"][7]["area"]["y"] = 423;
	$allMenu["actions"][7]["area"]["width"] = 327;
	$allMenu["actions"][7]["area"]["height"] = 197;
	$allMenu["actions"][7]["text"] = "กองทุนสวัสดิการ";
	$allMenu["actions"][8]["type"] = "message";
	$allMenu["actions"][8]["area"]["x"] = 703;
	$allMenu["actions"][8]["area"]["y"] = 425;
	$allMenu["actions"][8]["area"]["width"] = 333;
	$allMenu["actions"][8]["area"]["height"] = 197;
	$allMenu["actions"][8]["text"] = "ฌาปนกิจ";

	$arrPostData["messages"][0] = $allMenu;
	$arrPostData["replyToken"] = $reply_token;
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>