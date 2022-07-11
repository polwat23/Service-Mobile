<?php
$json = file_get_contents(__DIR__.'/../../config/config_linebot.json');
$config = json_decode($json,true);

$checkBeenBind = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE line_token = :line_id");
$checkBeenBind->execute([':line_id' => $user_id]);

$baseUrl = 'https://cdn.thaicoop.co/linebot/imagemap/'.$config["COOP_KEY"].'.jpg';
if($checkBeenBind->rowCount() > 0){
	
$datas = [];

$datas["type"] = "imagemap";
$datas["baseUrl"] = $baseUrl.'?_ignorid';
$datas["altText"] = "เมนูสหกรณ์";
$datas["baseSize"]["width"] = 1040;
$datas["baseSize"]["height"] = 625;
$datas["actions"][0]["type"] = "message";
$datas["actions"][0]["area"]["x"] = 4;
$datas["actions"][0]["area"]["y"] = 1;
$datas["actions"][0]["area"]["width"] = 346;
$datas["actions"][0]["area"]["height"] = 202;
$datas["actions"][0]["text"] = "ข้อมูลส่วนตัว";
$datas["actions"][1]["type"] = "message";
$datas["actions"][1]["area"]["x"] = 355;
$datas["actions"][1]["area"]["y"] = 2;
$datas["actions"][1]["area"]["width"] = 339;
$datas["actions"][1]["area"]["height"] = 201;
$datas["actions"][1]["text"] = "หุ้น";
$datas["actions"][2]["type"] = "message";
$datas["actions"][2]["area"]["x"] = 703;
$datas["actions"][2]["area"]["y"] = 4;
$datas["actions"][2]["area"]["width"] = 330;
$datas["actions"][2]["area"]["height"] = 202;
$datas["actions"][2]["text"] = "เงินฝาก";
$datas["actions"][3]["type"] = "message";
$datas["actions"][3]["area"]["x"] = 5;
$datas["actions"][3]["area"]["y"] = 214;
$datas["actions"][3]["area"]["width"] = 339;
$datas["actions"][3]["area"]["height"] = 197;
$datas["actions"][3]["text"] = "เงินกู้";
$datas["actions"][4]["type"] = "message";
$datas["actions"][4]["area"]["x"] = 357;
$datas["actions"][4]["area"]["y"] = 214;
$datas["actions"][4]["area"]["width"] = 335;
$datas["actions"][4]["area"]["height"] = 197;
$datas["actions"][4]["text"] = "ค้ำประกัน";
$datas["actions"][5]["type"] = "message";
$datas["actions"][5]["area"]["x"] = 706;
$datas["actions"][5]["area"]["y"] = 214;
$datas["actions"][5]["area"]["width"] = 327;
$datas["actions"][5]["area"]["height"] = 197;
$datas["actions"][5]["text"] = "เรียกเก็บประจำเดือน";
$datas["actions"][6]["type"] = "message";
$datas["actions"][6]["area"]["x"] = 9;
$datas["actions"][6]["area"]["y"] = 418;
$datas["actions"][6]["area"]["width"] = 332;
$datas["actions"][6]["area"]["height"] = 204;
$datas["actions"][6]["text"] = "ปันผล";
$datas["actions"][7]["type"] = "message";
$datas["actions"][7]["area"]["x"] = 357;
$datas["actions"][7]["area"]["y"] = 422;
$datas["actions"][7]["area"]["width"] = 332;
$datas["actions"][7]["area"]["height"] = 198;
$datas["actions"][7]["text"] = "สิทธิ์กู้โดยประมาณ";
$datas["actions"][8]["type"] = "message";
$datas["actions"][8]["area"]["x"] = 708;
$datas["actions"][8]["area"]["y"] = 422;
$datas["actions"][8]["area"]["width"] = 323;
$datas["actions"][8]["area"]["height"] = 200;
$datas["actions"][8]["text"] = "ใบเสร็จ";


	$arrPostData["messages"][0] = $datas;
	$arrPostData["replyToken"] = $reply_token;
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>