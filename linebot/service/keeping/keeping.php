<?php
if($lineLib->checkBindAccount($user_id)){
	$conoracle = $con->connecttooldoracle();
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	//keepingData
	$dateshow_kpmonth = $func->getConstant('dateshow_kpmonth');
	$keep_forward = $func->getConstant('process_keep_forward');
	$MonthForCheck = date('m');
	$DayForCheck = date('d');
	$getLastReceive = $conoracle->prepare("SELECT * FROM (SELECT MAX(recv_period) as MAX_RECV,RECEIPT_NO,RECEIVE_AMT
															FROM kptempreceive WHERE member_no = :member_no GROUP BY RECEIPT_NO,RECEIVE_AMT ORDER BY MAX_RECV DESC) WHERE rownum <= 1");
	$getLastReceive->execute([':member_no' => $member_no]);
	$rowLastRecv = $getLastReceive->fetch(PDO::FETCH_ASSOC);
	$checkHasBeenPay = $conoracle->prepare("SELECT RECV_PERIOD FROM kpmastreceive WHERE member_no = :member_no and recv_period = :max_recv and keeping_status = 1");
	$checkHasBeenPay->execute([
		':member_no' => $member_no,
		':max_recv' => $rowLastRecv["MAX_RECV"]
	]);
	$rowBeenPay = $checkHasBeenPay->fetch(PDO::FETCH_ASSOC);
	$max_recv = (int) substr($rowLastRecv["MAX_RECV"],4);
	$result = true;
	
	if($keep_forward == '1'){
		if($MonthForCheck < $max_recv){
			$result = false;
			
		}else{
			if($DayForCheck < $dateshow_kpmonth){
				$result = false;
			}
		}
	}else{
		if($DayForCheck < $dateshow_kpmonth){
			$result = false;
		}
	}
	if((isset($rowBeenPay["RECV_PERIOD"]) && $rowBeenPay["RECV_PERIOD"] != "") || (empty($rowLastRecv["MAX_RECV"]) && $rowLastRecv["MAX_RECV"] == "")){
		$result = false;
	}
	$groupData["RECEIVE_AMT"] = number_format($rowLastRecv["RECEIVE_AMT"],2);
	$groupData["RECV_PERIOD"] = $rowLastRecv["MAX_RECV"];
	$groupData["SLIP_NO"] = $rowLastRecv["RECEIPT_NO"];
	$groupData["MONTH_RECEIVE"] = $lib->convertperiodkp(TRIM($rowLastRecv["MAX_RECV"]));
	$getPaymentDetail = $conoracle->prepare("SELECT 
																	CASE kut.keepitemtype_code 
																	WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																	WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																	ELSE kut.keepitemtype_desc
																	END as TYPE_DESC,
																	kut.keepitemtype_grp as TYPE_GROUP,
																	case kut.keepitemtype_grp 
																		WHEN 'DEP' THEN kpd.description
																		WHEN 'LON' THEN kpd.loancontract_no
																	ELSE kpd.description END as PAY_ACCOUNT,
																	kpd.period,
																	NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																	NVL(kpd.PRINCIPAL_BALANCE,0) AS ITEM_BALANCE,
																	NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																	NVL(kpd.interest_payment,0) AS INT_BALANCE
																	FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																	kpd.keepitemtype_code = kut.keepitemtype_code
																	LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																	LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																	WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																	ORDER BY kut.SORT_IN_RECEIVE ASC");
	$getPaymentDetail->execute([
		':member_no' => $member_no,
		':recv_period' => $rowLastRecv["MAX_RECV"]
	]);
	$arrGroupDetail = array();
	while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
		$arrDetail = array();
		if(isset($rowDetail["DESCRIPTION"]) && $rowDetail["DESCRIPTION"] != ""){
			$arrDetail["TYPE_DESC"] = $rowDetail["DESCRIPTION"];
		}else{
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
		}
		if($rowDetail["TYPE_GROUP"] == 'SHR'){
			$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
		}else if($rowDetail["TYPE_GROUP"] == 'LON'){
			$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
			$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
			$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
			$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
		}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
			$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
			$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
		}else if($rowDetail["TYPE_GROUP"] == "OTH"){
			$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
			$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
		}
		if($rowDetail["ITEM_BALANCE"] > 0){
			$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
		}
		$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
		$arrGroupDetail[] = $arrDetail;
	}
	$groupData['SHOW_SLIP_REPORT'] = TRUE;
	$groupData['DETAIL'] = $arrGroupDetail;
	$recv_period = $rowLastRecv["MAX_RECV"];
	//keeping report 




	if($result){
		require_once('keeping_report.php');
		$keepingData = array();
		$keepingData["type"] = "flex";
		$keepingData["altText"] = "เรียกเก็บประจำเดือน ".($keep_forward??"-");
		$keepingData["contents"]["type"] = "bubble";
		$keepingData["contents"]["direction"] = "ltr";
		$keepingData["contents"]["body"]["type"] = "box";
		$keepingData["contents"]["body"]["layout"] = "vertical";
		$keepingData["contents"]["body"]["contents"][0]["type"] = "text";
		$keepingData["contents"]["body"]["contents"][0]["text"] = "เรียกเก็บประจำเดือน";
		$keepingData["contents"]["body"]["contents"][0]["weight"] = "bold";
		$keepingData["contents"]["body"]["contents"][0]["size"] = "lg";
		$keepingData["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
		$keepingData["contents"]["body"]["contents"][0]["align"] = "center";
		$keepingData["contents"]["body"]["contents"][1]["type"] = "text";
		$keepingData["contents"]["body"]["contents"][1]["text"] = ($groupData["MONTH_RECEIVE"]??"-");
		$keepingData["contents"]["body"]["contents"][1]["weight"] = "bold";
		$keepingData["contents"]["body"]["contents"][1]["size"] = "xs";
		$keepingData["contents"]["body"]["contents"][1]["color"] = "#1885C3";
		$keepingData["contents"]["body"]["contents"][2]["type"] = "box";
		$keepingData["contents"]["body"]["contents"][2]["layout"] = "horizontal";
		$keepingData["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
		$keepingData["contents"]["body"]["contents"][2]["contents"][0]["text"] = "เลขที่ใบเรียกเก็บ";
		$keepingData["contents"]["body"]["contents"][2]["contents"][0]["size"] = "xs";
		$keepingData["contents"]["body"]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
		$keepingData["contents"]["body"]["contents"][2]["contents"][1]["type"] = "text";
		$keepingData["contents"]["body"]["contents"][2]["contents"][1]["text"] = ($groupData["SLIP_NO"]??"-");;
		$keepingData["contents"]["body"]["contents"][2]["contents"][1]["size"] = "xs";
		$keepingData["contents"]["body"]["contents"][2]["contents"][1]["color"] = "#000000";
		$keepingData["contents"]["body"]["contents"][2]["contents"][1]["align"] = "end";
		$keepingData["contents"]["body"]["contents"][3]["type"] = "box";
		$keepingData["contents"]["body"]["contents"][3]["layout"] = "horizontal";
		$keepingData["contents"]["body"]["contents"][3]["contents"][0]["type"] = "text";
		$keepingData["contents"]["body"]["contents"][3]["contents"][0]["text"] = "ยอดเรียกเก็บ";
		$keepingData["contents"]["body"]["contents"][3]["contents"][0]["weight"] = "bold";
		$keepingData["contents"]["body"]["contents"][3]["contents"][0]["size"] = "sm";
		$keepingData["contents"]["body"]["contents"][3]["contents"][0]["color"] = "#EA5F0FFF";
		$keepingData["contents"]["body"]["contents"][3]["contents"][1]["type"] = "text";
		$keepingData["contents"]["body"]["contents"][3]["contents"][1]["text"] = ($groupData["RECEIVE_AMT"]??"-");
		$keepingData["contents"]["body"]["contents"][3]["contents"][1]["weight"] = "bold";
		$keepingData["contents"]["body"]["contents"][3]["contents"][1]["size"] = "sm";
		$keepingData["contents"]["body"]["contents"][3]["contents"][1]["color"] = "#EA5F0FFF";
		$keepingData["contents"]["body"]["contents"][3]["contents"][1]["align"] = "end";
		$keepingData["contents"]["body"]["contents"][4]["type"] = "box";
		$keepingData["contents"]["body"]["contents"][4]["layout"] = "vertical";
		$keepingData["contents"]["body"]["contents"][4]["margin"] = "md";
		
		$indexDetail = 0;

		if(sizeof($groupData["DETAIL"]>0)){
			
			foreach($groupData["DETAIL"] as $detailData){
				$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["type"] = "box";
				$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["layout"] = "vertical";
				$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][0]["type"] = "separator";
			
				$indexItem = 0;
				if(isset($detailData["TYPE_DESC"]) && !$detailData["TYPE_DESC"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["text"] = ($detailData["TYPE_DESC"]??'-');
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["weight"] = "bold";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["margin"] = "md";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
				if(isset($detailData["PERIOD"]) && !$detailData["PERIOD"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["text"] =  "งวด ".($detailData["PERIOD"]??'-');
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["weight"] = "bold";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["align"] = "end";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
				if(isset($detailData["ITEM_PAYMENT"]) && !$detailData["ITEM_PAYMENT"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "box";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["layout"] = "baseline";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["text"] = "ยอดเรียกเก็บ";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["text"] = ($detailData["ITEM_PAYMENT"]??'-')." บาท";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["weight"] = "bold";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["color"] = "#1885C3";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["align"] = "end";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
				
				if(isset($detailData["PAY_ACCOUNT"]) && !$detailData["PAY_ACCOUNT"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "box";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["layout"] = "baseline";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["text"] = "เลขสัญญา : ";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["weight"] = "regular";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["text"] = ($detailData["PAY_ACCOUNT"]??'-');
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["weight"] = "regular";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["align"] = "end";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
					
				if(isset($detailData["PRN_BALANCE"]) && !$detailData["PRN_BALANCE"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "box";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["layout"] = "baseline";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["text"] = "เงินต้น : ";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["text"] = ($detailData["PRN_BALANCE"]??'-')." บาท";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["align"] = "end";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
				
				if(isset($detailData["INT_BALANCE"]) && !$detailData["INT_BALANCE"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "box";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["layout"] = "baseline";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["text"] = "ดอกเบี้ย : ";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["text"] = ($detailData["INT_BALANCE"]??'-')." บาท";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["align"] = "end";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
				if(isset($detailData["ITEM_BALANCE"]) && !$detailData["ITEM_BALANCE"] == ""){
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "box";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["layout"] = "baseline";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["text"] = "คงเหลือ : ";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][0]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["type"] = "text";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["text"] = ($detailData["ITEM_BALANCE"]??'-')." บาท";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["size"] = "xs";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["color"] = "#AAAAAA";
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["contents"][1]["align"] = "end";
				}else{
					$indexItem++;
					$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["contents"][$indexItem]["type"] = "filler";
				}
				
				$indexDetail++;
			}
			
			if(isset($report_url) && $report_url !=''){
				$keepingData["contents"]["body"]["contents"][5]["type"] = "box";
				$keepingData["contents"]["body"]["contents"][5]["layout"] = "vertical";
				$keepingData["contents"]["body"]["contents"][5]["margin"] = "md";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["type"] = "button";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["action"]["type"] = "uri";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["action"]["label"] = "เรียกดูใบเรียกเก็บ";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["action"]["uri"] = $report_url;
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["color"] = ($themeColor??"#000000");
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["style"] = "primary";
			}else{
				$keepingData["contents"]["body"]["contents"][5]["type"] = "box";
				$keepingData["contents"]["body"]["contents"][5]["layout"] = "vertical";
				$keepingData["contents"]["body"]["contents"][5]["margin"] = "md";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["type"] = "button";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["action"]["type"] = "message";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["action"]["label"] = "เรียกดูใบเรียกเก็บ";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["action"]["text"] = "ไม่พบใบเรียกเก็บ";
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["color"] = ($themeColor??"#000000");;
				$keepingData["contents"]["body"]["contents"][5]["contents"][0]["style"] = "primary";
			}
		}else{

			$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["type"] = "text";
			$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["text"] = "ไม่พบข้อมูล";
			$keepingData["contents"]["body"]["contents"][4]["contents"][$indexDetail]["margin"] = "20px";
		}
	
		$arrPostData["messages"][0] = $keepingData;
		$arrPostData["replyToken"] = $reply_token; 
	}else{
		$messageResponse = "ไม่พบข้อมูลเรียกเก็บเดือนนี้";
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