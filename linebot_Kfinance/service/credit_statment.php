<?php  
if($checkBeenBind->rowCount() > 0){
	$datas = [];
	$datas["type"] = "flex";
	$datas["altText"] = "ใบเสร็จ";
	$datas["contents"]["type"] = "carousel";
	
	$no = 9;
	for($i=0; $i<4; $i++){
		$datas["contents"]["contents"][$i]["type"] = "bubble";
		$datas["contents"]["contents"][$i]["direction"] = "ltr";
		$datas["contents"]["contents"][$i]["body"]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["layout"] = "vertical";
		$datas["contents"]["contents"][$i]["body"]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][0]["text"] = "งวดที่ ".$no;
		$datas["contents"]["contents"][$i]["body"]["contents"][0]["weight"] = "bold";
		$datas["contents"]["contents"][$i]["body"]["contents"][0]["size"] = "md";
		$datas["contents"]["contents"][$i]["body"]["contents"][0]["align"] = "center";
		$datas["contents"]["contents"][$i]["body"]["contents"][1]["type"] = "separator";
		$datas["contents"]["contents"][$i]["body"]["contents"][1]["margin"] = "md";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["margin"] = "md";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][0]["text"] = "วันที่ชำระ";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][0]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][1]["text"] = "27 ธ.ค. 2564";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][1]["weight"] = "bold";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][1]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][1]["color"] = "#000000FF";
		$datas["contents"]["contents"][$i]["body"]["contents"][2]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][0]["text"] = "เงินต้น";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][0]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][1]["text"] = "1,475.00 บาท";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][1]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][1]["color"] = "#000000FF";
		$datas["contents"]["contents"][$i]["body"]["contents"][3]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][0]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][1]["text"] = "1,326.00 บาท";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][1]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][1]["color"] = "#35B84B";
		$datas["contents"]["contents"][$i]["body"]["contents"][4]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][0]["text"] = "ค่าปรับ";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][0]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][1]["text"] = "326.00 บาท";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][1]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][1]["color"] = "#FF0000";
		$datas["contents"]["contents"][$i]["body"]["contents"][5]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][0]["text"] = "ค่าติดตาม/ทวงถาม";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][0]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][0]["color"] = "#AAAAAA";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][1]["text"] = "326.00 บาท";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][1]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][1]["color"] = "#EA5F0FFF";
		$datas["contents"]["contents"][$i]["body"]["contents"][6]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][0]["text"] = "ส่วนลด";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][0]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][0]["color"] = "#AAAAAA";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][1]["text"] = "326.00 บาท";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][1]["size"] = "xxs";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][1]["color"] = "#000000";
		$datas["contents"]["contents"][$i]["body"]["contents"][7]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["layout"] = "horizontal";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][0]["text"] = "ยอดชำระเงิน";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][0]["size"] = "xs";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][0]["color"] = "#000000";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][1]["type"] = "text";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][1]["text"] = "2,900.00 บาท";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][1]["weight"] = "bold";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][1]["size"] = "sm";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][1]["color"] = "#1885C3";
		$datas["contents"]["contents"][$i]["body"]["contents"][8]["contents"][1]["align"] = "end";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["type"] = "box";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["layout"] = "vertical";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["margin"] = "md";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["borderWidth"] = "1px";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["cornerRadius"] = "10px";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["contents"][0]["type"] = "button";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["contents"][0]["action"]["type"] = "uri";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["contents"][0]["action"]["label"] = "เรียกดูใบเสร็จ";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["contents"][0]["action"]["uri"] = "https://linecorp.com";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["contents"][0]["height"] = "sm";
		$datas["contents"]["contents"][$i]["body"]["contents"][9]["contents"][0]["style"] = "primary";
		$no--;
	}
	

	
	
	$arrPostData['messages'][0] = $datas;
	$arrPostData['replyToken'] = $reply_token;


}else{
	$data = "ท่านยังไม่ได้ผูกบัญชีเพื่อดูข้อมูล กรุณาพิมพ์ รหัสประชาชน/เบอร์โทร  เช่น 1245784512457/0992408181";
	$dataTemplate = $lineLib->mergeTextMessage($data);
	$arrPostData['messages'][0] = $dataTemplate;
	$arrPostData['replyToken'] = $reply_token;
}
?>