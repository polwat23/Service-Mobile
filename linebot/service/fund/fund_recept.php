<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$limit = $func->getConstant('show_receiptfun_limit');
	$arrayGroupFund = array();
	$getReceiptFund = $conmssql->prepare("SELECT TOP ".$limit." DEPTSLIP_DATE , DEPTSLIP_NO , 
										(CASE WHEN CASH_TYPE = 'CSH'  THEN 'ชำระเงินสด'   WHEN CASH_TYPE ='TRN'  THEN 'ชำระเงินโอภายใน' 
										WHEN CASH_TYPE ='CBT' THEN 'ชำระเงินโอนธนาคาร' ELSE '' END )  AS RECEIPT,
										DEPTSLIP_AMT 
										FROM WCDEPTSLIP WHERE DEPTACCOUNT_NO = :fund_account AND item_status = 1 ");
	$getReceiptFund->execute([':fund_account' => $fund_account]);
	while($rowReceipt = $getReceiptFund->fetch(PDO::FETCH_ASSOC)){
		$arrayReceipt = array();		
		$arrayReceipt["DEPTSLIP_DATE"] = $lib->convertdate($rowReceipt["DEPTSLIP_DATE"],'d m Y');
		$arrayReceipt["DEPTSLIP_NO"] = $rowReceipt["DEPTSLIP_NO"];
		$arrayReceipt["RECEIPT"] = $rowReceipt["RECEIPT"]==''?'-':$rowReceipt["RECEIPT"];
		$arrayReceipt["DEPTSLIP_AMT"] = number_format($rowReceipt["DEPTSLIP_AMT"],2);
		$arrayGroupFund[] = $arrayReceipt;
	}
	if(sizeof($arrayGroupFund) > 0){
		$indexReceipt = 0;
		foreach($arrayGroupFund as $rowReceipt){
			require_once('fund_report_pdf.php');
			$datas = array();
			$datas["type"] = "flex";
			$datas["altText"] = "ใบเสร็จ : ".($rowReceipt["DEPTSLIP_NO"]??'-');
			$datas["contents"]["type"] = "carousel";
			$datas["contents"]["contents"][$indexReceipt]["type"] = "bubble";
			$datas["contents"]["contents"][$indexReceipt]["direction"] = "ltr";
			$datas["contents"]["contents"][$indexReceipt]["body"]["type"] = "box";
			$datas["contents"]["contents"][$indexReceipt]["body"]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["text"] = ($rowReceipt["DEPTSLIP_NO"]??'-');
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["weight"] = "bold";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["size"] = "md";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][0]["align"] = "center";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][1]["type"] = "separator";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][1]["margin"] = "sm";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["type"] = "box";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["margin"] = "md";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["text"] = "วันที่ใบเสร็จ";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["text"] = ($rowReceipt["DEPTSLIP_DATE"]??'-');
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][2]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["type"] = "box";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["text"] = "ประเภทใบเสร็จ";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["text"] = ($rowReceipt["RECEIPT"]??'-');
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][3]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["type"] = "box";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["text"] = "จำนวนเงิน";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["size"] = "md";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["text"] = ($rowReceipt["DEPTSLIP_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["size"] = "md";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["color"] = "#35B84B";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][4]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["type"] = "box";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["margin"] = "md";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["type"] = "button";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["action"]["type"] = "uri";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["action"]["label"] = "ดูใบเสร็จ";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["action"]["uri"] = ($url_report??"https://line.amn-corporation.com/convert/flex");
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["height"] = "sm";
			$datas["contents"]["contents"][$indexReceipt]["body"]["contents"][5]["contents"][0]["style"] = "primary";
			$arrPostData["messages"][0] = $datas;
			$arrPostData["replyToken"] = $reply_token;
		}
	}else{
		$messageResponse = "ไม่พบใบเสร็จ";
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