<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrAllLoan = array();
	$getSumAllContract = $conoracle->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lncontmaster WHERE member_no = :member_no
											and contract_status > 0 and contract_status <> 8");
	$getSumAllContract->execute([':member_no' => $member_no]);
	$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
	
	$getTotalContract = $conoracle->prepare("SELECT COUNT(LOANCONTRACT_NO) as TOTAL_CONTRACT  FROM lncontmaster WHERE member_no = :member_no
											and contract_status > 0 and contract_status <> 8");
	$getTotalContract->execute([':member_no' => $member_no]);
	$rowTotalContract = $getTotalContract->fetch(PDO::FETCH_ASSOC);
	
	$sum_loanbalance = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
	$total_contact = $rowTotalContract["TOTAL_CONTRACT"];
	$getContract = $conoracle->prepare("SELECT  lt.LOANTYPE_CODE AS LOANTYPE_CODE,lt.LOANTYPE_DESC AS LOAN_TYPE,RTRIM(ln.LOANCONTRACT_NO) as LOANCONTRACT_NO,ln.principal_balance as LOAN_BALANCE,
										ln.loanapprove_amt as APPROVE_AMT,ln.STARTCONT_DATE,ln.PERIOD_PAYMENT,ln.period_payamt as PERIOD,
										ln.LAST_PERIODPAY as LAST_PERIOD,
										(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
										FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
										WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
	$getContract->execute([':member_no' => $member_no]);
	$amt_balance = 0;
	while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
		$getSumLoanType = $conoracle->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE 
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
		$arrGroupContract['LOANTYPE_CODE'] = $rowContract["LOANTYPE_CODE"];
		$arrGroupContract['AMT_BALANCE'] = number_format($rowSumLoanType["SUM_LOANBALANCE"],2);
		if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
			($arrGroupContract['CONTRACT'])[] = $arrContract;
			$arrAllLoan[] = $arrGroupContract;
		}else{
			($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
		}
	}
	
	$loanData = array();
	$loanData["type"] = "flex";
	$loanData["altText"] = "เงินกู้";
	$loanData["contents"]["type"] = "carousel";
	
	$loanData["contents"]["contents"][0]["type"] = "bubble";
	$loanData["contents"]["contents"][0]["direction"] = "ltr";
	$loanData["contents"]["contents"][0]["body"]["type"] = "box";
	$loanData["contents"]["contents"][0]["body"]["layout"] = "vertical";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["type"] = "box";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["layout"] = "vertical";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["margin"] = "xxl";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["type"] = "box";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["layout"] = "horizontal";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["margin"] = "md";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][0]["type"] = "text";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][0]["text"] = "เงินกู้";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][0]["weight"] = "bold";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][0]["size"] = "xl";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][0]["color"] = ($themeColor??"#000000");
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][0]["align"] = "start";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["type"] = "text";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["text"] = "จำนวน";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][0]["type"] = "span";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][0]["text"] = "จำนวน ";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][1]["type"] = "span";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][1]["text"] = ($total_contact??"0");
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][1]["weight"] = "bold";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][2]["type"] = "span";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["contents"][2]["text"] = " สัญญา";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["size"] = "xs";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["contents"][1]["align"] = "end";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["type"] = "image";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["url"] = "https://cdn.thaicoop.co/icon/bill.png";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["align"] = "start";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["size"] = "xs";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["type"] = "text";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["text"] = "หนี้รวมทุกสัญญา";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["weight"] = "bold";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["color"] = "#2068C7FF";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["align"] = "center";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["position"] = "absolute";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["offsetTop"] = "35px";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][2]["offsetEnd"] = "0px";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["type"] = "text";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["text"] = ($sum_loanbalance??0.00)." บาท";;
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["weight"] = "bold";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["size"] = "xl";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["color"] = "#FF0000";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["align"] = "end";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["position"] = "absolute";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["offsetTop"] = "55px";
	$loanData["contents"]["contents"][0]["body"]["contents"][0]["contents"][3]["offsetEnd"] = "0px";

	
	
	$indexLoanData = 1;
	foreach($arrAllLoan as $loanTypeData){
		$loanData["contents"]["contents"][$indexLoanData]["type"] = "bubble";
		$loanData["contents"]["contents"][$indexLoanData]["direction"] = "ltr";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["type"] = "box";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["layout"] = "vertical";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["type"] = "box";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["layout"] = "vertical";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][0]["type"] = "text";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][0]["text"] = ($loanTypeData["TYPE_LOAN"]??'-');
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][0]["weight"] = "bold";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][0]["color"] = ($themeColor??"#000000");
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][0]["align"] = "start";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][0]["offsetEnd"] = "0px";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["type"] = "text";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["text"] = "text";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["size"] = "xs";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["align"] = "end";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "span";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = "จำนวน";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "span";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][1]["text"] = ' '.(sizeof($loanTypeData["CONTRACT"])??0).' ';
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][1]["weight"] = "bold";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][2]["type"] = "span";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][1]["contents"][2]["text"] = "สัญญา";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["type"] = "text";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["text"] = ($loanTypeData["AMT_BALANCE"]??0)." บาท";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["weight"] = "bold";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["size"] = "lg";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["color"] = "#FF0000";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["align"] = "end";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["contents"][2]["offsetEnd"] = "0px";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["type"] = "box";
		$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["layout"] = "vertical";
		$loanDetail = $loanTypeData["CONTRACT"]??[];
		$indexContract = 0;
		if(sizeof($loanTypeData["CONTRACT"])>0){
			foreach($loanDetail as $contract ){
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["type"] = "box";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["layout"] = "vertical";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["type"] = "box";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["layout"] = "vertical";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["margin"] = "md";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["type"] = "box";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["layout"] = "horizontal";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][0]["type"] = "text";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][0]["text"] = ($contract["CONTRACT_NO"]??'-');
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][0]["size"] = "xxs";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][1]["type"] = "text";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][1]["text"] = "ชำระแล้ว ".($contract["SUCCEED"]??'0%');
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][1]["weight"] = "bold";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][1]["size"] = "xxs";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][1]["color"] = "#2068C7FF";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][0]["contents"][1]["align"] = "end";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["type"] = "box";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["layout"] = "vertical";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["type"] = "box";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["layout"] = "vertical";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["height"] = "6px";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["backgroundColor"] = "#A1F9AFFF";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["contents"][0]["type"] = "box";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["contents"][0]["layout"] = "vertical";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["contents"][0]["height"] = "6px";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["contents"][0]["backgroundColor"] = "#35B84B";
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["contents"][0]["width"] = ($contract["SUCCEED"]??'0%');
				$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][1]["contents"][$indexContract]["contents"][0]["contents"][1]["contents"][0]["contents"][0]["contents"][0]["type"] = "filler";
				$indexContract++;
			}
		}else{
			$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["type"] = "text";
			$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["text"] = "ไม่พบข้อมูล";
			$loanData["contents"]["contents"][$indexLoanData]["body"]["contents"][0]["margin"] = "md";
		}
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["type"] = "box";
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["layout"] = "horizontal";
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["type"] = "button";
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["action"]["type"] = "message";
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["action"]["label"] = "ดูรายละเอียด";
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["action"]["text"] = "ประเภทเงินกู้:".($loanTypeData["LOANTYPE_CODE"]??'-');
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["color"] = ($themeColor??"#000000");
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["height"] = "sm";
		$loanData["contents"]["contents"][$indexLoanData]["footer"]["contents"][0]["style"] = "primary";
		$indexLoanData++;
	}
	$arrPostData["messages"][0] = $loanData;
	$arrPostData["replyToken"] = $reply_token; 
}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>