<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrAllLoan = array();
	$getSumAllContract = $conmssql->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lncontmaster WHERE member_no = :member_no
											and contract_status > 0 and contract_status <> 8");
	$getSumAllContract->execute([':member_no' => $member_no]);
	$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
	
	$getTotalContract = $conmssql->prepare("SELECT COUNT(LOANCONTRACT_NO) as TOTAL_CONTRACT  FROM lncontmaster WHERE member_no = :member_no
											and contract_status > 0 and contract_status <> 8");
	$getTotalContract->execute([':member_no' => $member_no]);
	$rowTotalContract = $getTotalContract->fetch(PDO::FETCH_ASSOC);
	
	$sum_loanbalance = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
	$total_contact = $rowTotalContract["TOTAL_CONTRACT"];
	$getContract = $conmssql->prepare("SELECT  lt.LOANTYPE_CODE AS LOANTYPE_CODE,lt.LOANTYPE_DESC AS LOAN_TYPE,RTRIM(ln.LOANCONTRACT_NO) as LOANCONTRACT_NO,ln.principal_balance as LOAN_BALANCE,
										ln.loanapprove_amt as APPROVE_AMT,ln.STARTCONT_DATE,ln.PERIOD_PAYMENT,ln.period_payamt as PERIOD,
										ln.LAST_PERIODPAY as LAST_PERIOD,
										(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
										FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
										WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8 and (lt.LOANTYPE_CODE = :loantype_code or lt.LOANTYPE_DESC  = :loantype_desc )");
	$getContract->execute([
		':member_no' => $member_no,
		':loantype_code' => $loan_type,
		':loantype_desc' => $loan_type
	]);
	$amt_balance = 0;
	while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
		$getSumLoanType = $conmssql->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE 
											  FROM lncontmaster WHERE member_no = :member_no and LOANTYPE_CODE = :loantype_code
											  and contract_status > 0 and contract_status <> 8");
		$getSumLoanType->execute([
			':member_no' => $member_no,
			':loantype_code' => $rowContract["LOANTYPE_CODE"]
		]);
		$rowSumLoanType = $getSumLoanType->fetch(PDO::FETCH_ASSOC);
		$paid = $rowContract["APPROVE_AMT"] - $rowContract["LOAN_BALANCE"];
		$arrGroupContract = array();
		$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
		$arrContract = array();
		$arrContract["CONTRACT_NO"] = $contract_no;
		$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
		$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
		$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
		$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
		$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["STARTCONT_DATE"],'D m Y');
		$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
		$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
		$arrContract["PAID"] = $rowContract["APPROVE_AMT"] - $rowContract["LOAN_BALANCE"];
		$arrContract["SUCCEED"] = number_format(($paid*100)/$rowContract["APPROVE_AMT"],2).'%';
		$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
		$arrGroupContract['AMT_BALANCE'] = number_format($rowSumLoanType["SUM_LOANBALANCE"],2);
		if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
			($arrGroupContract['CONTRACT'])[] = $arrContract;
			$arrAllLoan[] = $arrGroupContract;
		}else{
			($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
		}
	}
	
	$loanTypeGroup = ($arrAllLoan[0]["CONTRACT"]??[]);
	$type_loan = ($arrAllLoan[0]["TYPE_LOAN"]??'-');

	$datas = array();
	$datas["type"] = "flex";
	$datas["altText"] = $type_loan;
	$datas["contents"]["type"] = "carousel";
	
	if(sizeof($loanTypeGroup)>0){
		$indexContents = 0;
		foreach($loanTypeGroup as $rowLoanType){
			$datas["contents"]["contents"][$indexContents]["type"] = "bubble";
			$datas["contents"]["contents"][$indexContents]["direction"] = "ltr";
			$datas["contents"]["contents"][$indexContents]["body"]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["text"] = $type_loan;
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["color"] = ($themeColor??"#000000");
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["margin"] = "md";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = "เลขที่สัญญา";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["text"] = ($rowLoanType["CONTRACT_NO"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][0]["text"] = "วันที่เริ่มสัญญา";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][1]["text"] = ($rowLoanType["STARTCONT_DATE"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][1]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][1]["color"] = "#2068C7FF";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["text"] = "วงเงินกู้";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["text"] = ($rowLoanType["APPROVE_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["color"] = "#FF0000";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["text"] = "งวดทั้งหมด";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["text"] = ($rowLoanType["PERIOD"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["color"] = "#000000";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][0]["text"] = "ชำระต่อเดือน";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][0]["size"] = "xs";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][1]["text"] = ($rowLoanType["PERIOD_PAYMENT"]??'-').' บาท';
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][1]["color"] = "#000000";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["type"] = "separator";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["margin"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][7]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][7]["text"] = "หนี้คงเหลือ";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][7]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][7]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][7]["margin"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["text"] = ($rowLoanType["LOAN_BALANCE"]??'-').' บาท';
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["size"] = "lg";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["color"] = "#16A085";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][8]["margin"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["margin"] = "md";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["contents"][0]["type"] = "button";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["contents"][0]["action"]["type"] = "message";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["contents"][0]["action"]["label"] = "ดูรายการเคลื่อนไหว";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["contents"][0]["action"]["text"] = "รายการเคลื่อนไหวเงินกู้:".($rowLoanType["CONTRACT_NO"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["contents"][0]["color"] = ($themeColor??"#000000");
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][9]["contents"][0]["style"] = "primary";
			$indexContents++;
		}
	}else{
		$datas["contents"]["contents"][0]["type"] = "bubble";
		$datas["contents"]["contents"][0]["direction"] = "ltr";
		$datas["contents"]["contents"][0]["body"]["type"] = "box";
		$datas["contents"]["contents"][0]["body"]["layout"] = "vertical";
		$datas["contents"]["contents"][0]["body"]["contents"][0]["type"] = "text";
		$datas["contents"]["contents"][0]["body"]["contents"][0]["text"] = "ไม่พบข้อมูล";
		$datas["contents"]["contents"][0]["body"]["contents"][0]["margin"] = "md";
	}
	$arrPostData["messages"][0] = $datas;
	$arrPostData["replyToken"] = $reply_token; 
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>