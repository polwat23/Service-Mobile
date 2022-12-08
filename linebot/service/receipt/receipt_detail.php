<?php
if($lineLib->checkBindAccount($user_id)){
	require_once('receipt_create_pdf.php');
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	
	
	$limit_period = $func->getConstant('limit_kpmonth');
	$arrayGroupPeriod = array();
	$getPeriodKP = $conmssql->prepare("SELECT RECV_PERIOD,RECEIPT_DATE,RECEIPT_NO,RECEIVE_AMT,KEEPING_STATUS 
									    from kpmastreceive where member_no = :member_no and  (RECV_PERIOD = :recv_period or RECEIPT_NO = :receipt_no )
										ORDER BY recv_period DESC");
	$getPeriodKP->execute([
			':member_no' => $member_no,
			':recv_period' => $recv_period, 
			':receipt_no' => $recv_period
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
	
	$showSplitSlip = $func->getConstant('show_split_slip_report');
	$arrGroupDetail = array();
	$getDetailKP = $conmssql->prepare("SELECT 
											CASE kut.system_code 
											WHEN 'LON' THEN ISNULL(LT.LOANTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
											WHEN 'DEP' THEN ISNULL(DP.DEPTTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
											ELSE kut.keepitemtype_desc
											END as TYPE_DESC,
											kpd.SEQ_NO,
											kut.keepitemtype_grp as TYPE_GROUP,
											kpd.MONEY_RETURN_STATUS,
											kpd.ADJUST_ITEMAMT,
											kpd.ADJUST_PRNAMT,
											kpd.ADJUST_INTAMT,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.PERIOD,
											ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
											ISNULL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
											LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											ORDER BY kut.SORT_IN_RECEIVE ASC");
	
	$getDetailKP->execute([
		':member_no' => $member_no,
		':recv_period' => ($arrayGroupPeriod[0]["PERIOD"]??NULL)
	]);
	while($rowDetail = $getDetailKP->fetch(PDO::FETCH_ASSOC)){
		$arrDetail = array();
		$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
		if($rowDetail["TYPE_GROUP"] == 'SHR'){
			$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
		}else if($rowDetail["TYPE_GROUP"] == 'LON'){
			$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
			$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
			$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
			}else{
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}
		}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
			$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
			$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
		}else if($rowDetail["TYPE_GROUP"] == "OTH"){
			$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
			$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
		}
		if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ADJUST_ITEMAMT"],2);
		}else{
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
		}
		$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
		$arrDetail["SEQ_NO"] = $rowDetail["SEQ_NO"];
		$arrGroupDetail[] = $arrDetail;
	}
	$list['SPLIT_SLIP'] = $showSplitSlip == "1" ? TRUE : FALSE;
	$list['SHOW_SLIP_REPORT'] = TRUE;
	$list['DETAIL'] = $arrGroupDetail;
	
	$head = ($arrayGroupPeriod[0]??NULL);
	$fetchIntAccum = $conmssql->prepare("SELECT INTEREST_ACCUM FROM KPMASTRECEIVE WHERE MEMBER_NO = :member_no AND RECV_PERIOD = :recv_period");
	$fetchIntAccum->execute([
		':member_no' => $member_no,
		':recv_period' => $dataComing["recv_period"]
	]);
	$rowIntAccum = $fetchIntAccum->fetch(PDO::FETCH_ASSOC);
	$header["interest_accum"] =  $rowIntAccum["INTEREST_ACCUM"];
	if(sizeof($arrayGroupPeriod)>0){
		require_once('receipt_report.php');
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);	
		if($arrayPDF["RESULT"]){
			$report_url = $config["URL_SERVICE"].$arrayPDF["PATH"];			
		}else{
			$report_url = false;
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0044",
				":error_desc" => "สร้าง PDF ไม่ได้ "."\n".json_encode($data),
				":error_device" => 'Line Bot'
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "สร้างไฟล์ PDF ไม่ได้ ".$filename."\n"."DATA => ".json_encode($data);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0044";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
		}
		$receiptDetailData = array();
		$receiptDetailData["type"] = "flex";
		$receiptDetailData["altText"] = "ใบเสร็จ ".$recv_period;
		$receiptDetailData["contents"]["type"] = "bubble";
		$receiptDetailData["contents"]["direction"] = "ltr";
		$receiptDetailData["contents"]["body"]["type"] = "box";
		$receiptDetailData["contents"]["body"]["layout"] = "vertical";
		$receiptDetailData["contents"]["body"]["contents"][0]["type"] = "text";
		$receiptDetailData["contents"]["body"]["contents"][0]["text"] = ($head["MONTH_RECEIVE"]??'-');
		$receiptDetailData["contents"]["body"]["contents"][0]["weight"] = "bold";
		$receiptDetailData["contents"]["body"]["contents"][0]["size"] = "lg";
		$receiptDetailData["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
		if(isset($report_url) && $report_url !=''){
			$receiptDetailData["contents"]["body"]["contents"][1]["type"] = "box";
			$receiptDetailData["contents"]["body"]["contents"][1]["layout"] = "vertical";
			$receiptDetailData["contents"]["body"]["contents"][1]["margin"] = "md";
			$receiptDetailData["contents"]["body"]["contents"][1]["justifyContent"] = "center";
			$receiptDetailData["contents"]["body"]["contents"][1]["action"]["type"] = "uri";
			$receiptDetailData["contents"]["body"]["contents"][1]["action"]["label"] = "label";
			$receiptDetailData["contents"]["body"]["contents"][1]["action"]["uri"] = $report_url; 
			$receiptDetailData["contents"]["body"]["contents"][1]["offsetStart"] = "110px";
			$receiptDetailData["contents"]["body"]["contents"][1]["width"] = "150px";
			$receiptDetailData["contents"]["body"]["contents"][1]["height"] = "30px";
			$receiptDetailData["contents"]["body"]["contents"][1]["borderWidth"] = "1px";
			$receiptDetailData["contents"]["body"]["contents"][1]["backgroundColor"] = ($themeColor??"#000000");
			$receiptDetailData["contents"]["body"]["contents"][1]["cornerRadius"] = "10px";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["type"] = "text";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["text"] = "เรียกดูใบเสร็จรับเงิน";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["color"] = "#FFFFFF";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["align"] = "center";
		
		}else{
			$receiptDetailData["contents"]["body"]["contents"][1]["type"] = "box";
			$receiptDetailData["contents"]["body"]["contents"][1]["layout"] = "vertical";
			$receiptDetailData["contents"]["body"]["contents"][1]["margin"] = "md";
			$receiptDetailData["contents"]["body"]["contents"][1]["justifyContent"] = "center";
			$receiptDetailData["contents"]["body"]["contents"][1]["action"]["type"] = "message";
			$receiptDetailData["contents"]["body"]["contents"][1]["action"]["label"] = "label";
			$receiptDetailData["contents"]["body"]["contents"][1]["action"]["text"] = "ไม่พบใบเสร็จ"; 
			$receiptDetailData["contents"]["body"]["contents"][1]["offsetStart"] = "110px";
			$receiptDetailData["contents"]["body"]["contents"][1]["width"] = "150px";
			$receiptDetailData["contents"]["body"]["contents"][1]["height"] = "30px";
			$receiptDetailData["contents"]["body"]["contents"][1]["borderWidth"] = "1px";
			$receiptDetailData["contents"]["body"]["contents"][1]["backgroundColor"] = "#E3519D";
			$receiptDetailData["contents"]["body"]["contents"][1]["cornerRadius"] = "10px";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["type"] = "text";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["text"] = "เรียกดูใบเสร็จรับเงิน";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["color"] = "#FFFFFF";
			$receiptDetailData["contents"]["body"]["contents"][1]["contents"][0]["align"] = "center";
		}
		
		$receiptDetailData["contents"]["body"]["contents"][2]["type"] = "box";
		$receiptDetailData["contents"]["body"]["contents"][2]["layout"] = "horizontal";
		$receiptDetailData["contents"]["body"]["contents"][2]["margin"] = "md";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][0]["text"] = "เลขที่ใบเสร็จ";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][0]["size"] = "md";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][1]["type"] = "text";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][1]["text"] = "6406008374";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][1]["size"] = "md";
		$receiptDetailData["contents"]["body"]["contents"][2]["contents"][1]["align"] = "end";
		$receiptDetailData["contents"]["body"]["contents"][3]["type"] = "box";
		$receiptDetailData["contents"]["body"]["contents"][3]["layout"] = "horizontal";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][0]["type"] = "text";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][0]["text"] = "วันที่ใบเสร็จ";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][0]["size"] = "md";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][1]["type"] = "text";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][1]["text"] = ($head["SLIP_DATE"]??'-');
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][1]["size"] = "md";
		$receiptDetailData["contents"]["body"]["contents"][3]["contents"][1]["align"] = "end";
		$receiptDetailData["contents"]["body"]["contents"][4]["type"] = "text";
		$receiptDetailData["contents"]["body"]["contents"][4]["text"] = "ยอดรวม ";
		$receiptDetailData["contents"]["body"]["contents"][4]["size"] = "lg";
		$receiptDetailData["contents"]["body"]["contents"][4]["color"] = "#35B84B";
		$receiptDetailData["contents"]["body"]["contents"][4]["contents"][0]["type"] = "span";
		$receiptDetailData["contents"]["body"]["contents"][4]["contents"][0]["text"] = "ยอดรวม :";
		$receiptDetailData["contents"]["body"]["contents"][4]["contents"][1]["type"] = "span";
		$receiptDetailData["contents"]["body"]["contents"][4]["contents"][1]["text"] = ($head["RECEIVE_AMT"]??'-');
		$receiptDetailData["contents"]["body"]["contents"][4]["contents"][1]["weight"] = "bold";
		$receiptDetailData["contents"]["body"]["contents"][5]["type"] = "box";
		$receiptDetailData["contents"]["body"]["contents"][5]["layout"] = "vertical";
		
		if(sizeof($arrGroupDetail)>0){
			$indexGroupDetail = 0;
			foreach($arrGroupDetail as $rowReceiptData){	
				$titleLink = '-';
				if(isset($rowReceiptData["PAY_ACCOUNT"]) && !$rowReceiptData["PAY_ACCOUNT"] == ""){
					$titleLink = $rowReceiptData["PAY_ACCOUNT"];
				}else{
					$titleLink = $rowReceiptData["TYPE_DESC"]??'-';
				}
			
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["type"] = "box";
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["layout"] = "vertical";
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][0]["type"] = "separator";
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][0]["margin"] = "xl";
				if(isset($rowReceiptData["TYPE_DESC"]) && !$rowReceiptData["TYPE_DESC"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["text"] = ($rowReceiptData["TYPE_DESC"]??'-');
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["weight"] = "bold";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["color"] = "#1885C3";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["margin"] = "md";
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][1]["type"] = "filler";
				}
				
				if(isset($rowReceiptData["PERIOD"]) && !$rowReceiptData["PERIOD"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][2]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][2]["text"] = "งวด ".($rowReceiptData["PERIOD"]??'-');
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][2]["weight"] = "bold";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][2]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][2]["align"] = "end";
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][2]["type"] = "filler";
				}
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["type"] = "box";
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["layout"] = "baseline";
				if(isset($rowReceiptData["ITEM_PAYMENT"]) && !$rowReceiptData["ITEM_PAYMENT"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][0]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][0]["text"] = "ยอดเรียกเก็บ";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][0]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][1]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][1]["text"] = ($rowReceiptData["ITEM_PAYMENT"]??'-')." บาท";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][1]["weight"] = "bold";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][1]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][1]["color"] = "#2C4084FF";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][1]["align"] = "end";
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][3]["contents"][0]["type"] = "filler";
				}
	
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["type"] = "box";
				$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["layout"] = "baseline";
				if(isset($rowReceiptData["PAY_ACCOUNT_LABEL"]) && !$rowReceiptData["PAY_ACCOUNT_LABEL"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][0]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][0]["text"] = ($rowReceiptData["PAY_ACCOUNT_LABEL"]??'-').':'; 
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][0]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][0]["color"] = "#AAAAAA";
				
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][0]["type"] = "filler";
				}
				
				if(isset($rowReceiptData["PAY_ACCOUNT"]) && !$rowReceiptData["PAY_ACCOUNT"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][1]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][1]["text"] = ($rowReceiptData["PAY_ACCOUNT"]??'-'); 
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][1]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][1]["align"] = "end";
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][4]["contents"][1]["type"] = "filler";
				}

				if(isset($rowReceiptData["PRN_BALANCE"]) && !$rowReceiptData["PRN_BALANCE"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["layout"] = "baseline";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][0]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][0]["text"] = "เงินต้น :";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][0]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][0]["color"] = "#AAAAAA";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][1]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][1]["text"] = ($rowReceiptData["PRN_BALANCE"]??'-')." บาท";  
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][1]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][1]["align"] = "end";
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["layout"] = "vertical";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][5]["contents"][0]["type"] = "filler";
				}
				
				if(isset($rowReceiptData["INT_BALANCE"]) && !$rowReceiptData["INT_BALANCE"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["layout"] = "baseline";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][0]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][0]["text"] = "ดอกเบี้ย :";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][0]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][0]["color"] = "#AAAAAA";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][1]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][1]["text"] = ($rowReceiptData["INT_BALANCE"]??'-')." บาท"; 
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][1]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][1]["align"] = "end";
				
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["layout"] = "vertical";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][6]["contents"][0]["type"] = "filler";
				}
			
				if(isset($rowReceiptData["ITEM_BALANCE"]) && !$rowReceiptData["ITEM_BALANCE"] == ""){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["layout"] = "baseline";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][0]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][0]["text"] = "คงเหลือ :";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][0]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][0]["color"] = "#AAAAAA";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][1]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][1]["text"] = ($rowReceiptData["ITEM_BALANCE"]??'-')." บาท"; 
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][1]["size"] = "sm";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][1]["align"] = "end";
				
				}else{
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["layout"] = "vertical";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][7]["contents"][0]["type"] = "filler";
				}
				/*
				if(isset($receipt_url) && $receipt_url !=''){
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["type"] = "box";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["layout"] = "vertical";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["margin"] = "md";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["height"] = "35px";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["justifyContent"] = "center";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["backgroundColor"] = "#E3519D";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["cornerRadius"] = "10px";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["type"] = "text";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["text"] = 'เรียกดูใบเสร็จรับเงิน '.$titleLink;
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["weight"] = "bold";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["action"]["type"] = "uri";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["action"]["label"] = "label";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["action"]["uri"] = $receipt_url;
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["size"] = "xs";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["color"] = "#FFFFFF";
					$receiptDetailData["contents"]["body"]["contents"][5]["contents"][$indexGroupDetail] ["contents"][8]["contents"][0]["align"] = "center";
					
				}
				*/

				$indexGroupDetail++;
			}
			
		}else{
		
			$receiptDetailData["contents"]["body"]["contents"][5]["contents"][0] ["type"] = "text";
			$receiptDetailData["contents"]["body"]["contents"][5]["contents"][0] ["text"] = "ไม่พบข้อมูล";
			$receiptDetailData["contents"]["body"]["contents"][5]["contents"][0] ["margin"] = "md";
		}
		$arrPostData["messages"][0] = $receiptDetailData;
		$arrPostData["replyToken"] = $reply_token; 
	}else{
		$messageResponse = "ไม่พบข้อมูลใบเสร็จ ".$recv_period;
		$dataPrepare = $lineLib->prepareMessageText($messageResponse);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["replyToken"] = $reply_token;
	}

}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>