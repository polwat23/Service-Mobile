<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$limit_period = 12;
	$arrayGroupPeriod = array();
	$getPeriodKP = $conmssql->prepare("SELECT TOP ". $limit_period." RECV_PERIOD,RECEIPT_DATE,RECEIPT_NO,RECEIVE_AMT,KEEPING_STATUS 
														from kpmastreceive where member_no = :member_no ORDER BY recv_period DESC");
	$getPeriodKP->execute([
			':member_no' => $member_no
	]);
	while($rowPeriod = $getPeriodKP->fetch(PDO::FETCH_ASSOC)){
		$arrKpmonth = array();
		$arrKpmonth["PERIOD"] = $rowPeriod["RECV_PERIOD"];
		$arrKpmonth["MONTH_RECEIVE"] = $lib->convertperiodkp(TRIM($rowPeriod["RECV_PERIOD"]));
		$getKPDetail = $conmssql->prepare("SELECT ISNULL(SUM(KPD.ITEM_PAYMENT * KUT.SIGN_FLAG),0) AS ITEM_PAYMENT 
												FROM KPMASTRECEIVEDET KPD
												LEFT JOIN KPUCFKEEPITEMTYPE KUT ON 
												KPD.KEEPITEMTYPE_CODE = KUT.KEEPITEMTYPE_CODE
												where kpd.member_no = :member_no and kpd.recv_period = :recv_period");
		$getKPDetail->execute([
			':member_no' => $member_no,
			':recv_period' => $rowPeriod["RECV_PERIOD"]
		]);
		$rowKPDetali = $getKPDetail->fetch(PDO::FETCH_ASSOC);
		$arrKpmonth["SLIP_NO"] = $rowPeriod["RECEIPT_NO"];
		$arrKpmonth["SLIP_DATE"] = $lib->convertdate($rowPeriod["RECEIPT_DATE"],'d m Y');
		if(isset($rowPeriod["RECEIVE_AMT"]) && $rowPeriod["RECEIVE_AMT"] != ""){
			$arrKpmonth["RECEIVE_AMT"] = number_format($rowPeriod["RECEIVE_AMT"],2);
		}else{
			$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["ITEM_PAYMENT"],2);
		}
		if($rowPeriod["KEEPING_STATUS"] == '-99' || $rowPeriod["KEEPING_STATUS"] == '-9'){
			$arrKpmonth["IS_CANCEL"] = TRUE;
		}else{
			$arrKpmonth["IS_CANCEL"] = FALSE;
		}
		$arrayGroupPeriod[] = $arrKpmonth;
	}
	
	if(sizeof($arrayGroupPeriod)>0){
		$receiptData = array();
		$receiptData["type"] = "flex";
		$receiptData["altText"] = "ใบเสร็จ";
		$receiptData["contents"]["type"] = "carousel";
		$indexReceipt = 0;
		foreach($arrayGroupPeriod as $rowReceipt){
			$receiptData["contents"]["contents"][$indexReceipt]["type"] = "bubble";
			$receiptData["contents"]["contents"][$indexReceipt]["direction"] = "ltr";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["type"] = "box";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["layout"] = "vertical";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["type"] = "text";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["text"] = ($rowReceipt["MONTH_RECEIVE"]??'-'); 
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["weight"] = "bold";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["size"] = "lg";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["align"] = "center";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][1]["type"] = "separator";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][1]["margin"] = "md";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["type"] = "box";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["layout"] = "horizontal";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["margin"] = "md";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["type"] = "text";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["text"] = "เลขที่ใบเสร็จ ";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["size"] = "sm";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["type"] = "text";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["text"] = ($rowReceipt["SLIP_NO"]??'-');
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["size"] = "sm";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["align"] = "end";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["type"] = "box";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["layout"] = "horizontal";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["type"] = "text";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["text"] = "วันที่ใบเสร็จ";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["size"] = "sm";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["type"] = "text";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["text"] = ($rowReceipt["SLIP_DATE"]??'-');
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["size"] = "sm";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["align"] = "end";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["type"] = "text";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["margin"] = "md";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["type"] = "span";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["text"] = "ยอดรวม ";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["weight"] = "bold";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["size"] = "md";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["color"] = "#35B84B";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["type"] = "span";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["text"] = ($rowReceipt["RECEIVE_AMT"]??'-').' บาท';
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["weight"] = "bold";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["size"] = "md";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["color"] = "#35B84B";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["type"] = "box";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["layout"] = "vertical";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["margin"] = "md";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["type"] = "button";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["action"]["type"] = "message";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["action"]["label"] = "รายระเอียด";
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["action"]["text"] = "ใบเสร็จ:".($rowReceipt["PERIOD"]??'-');
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["color"] = ($themeColor??"#000000");
			$receiptData["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["style"] = "primary";
			$indexReceipt++;
		}
		$arrPostData["messages"][0] = $receiptData;
		$arrPostData["replyToken"] = $reply_token;
	}else{
		$messageResponse = "ไม่พบข้อมูลใบเสร็จ";
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
	}
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>