<?php
if($lineLib->checkBindAccount($user_id)){

	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$contract_no = $loanContract_no; ///preg_replace('/-/','',$loanContract_no)
	$arrAllLoan = array();
	$getSumAllContract = $conmssql->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lncontmaster WHERE member_no = :member_no
											and contract_status > 0 and contract_status <> 8");
	$getSumAllContract->execute([':member_no' => $member_no]);
	$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
	$sum_loanbalance = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
	$getContract = $conmssql->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,RTRIM(ln.LOANCONTRACT_NO) as LOANCONTRACT_NO,ln.principal_balance as LOAN_BALANCE,
										ln.loanapprove_amt as APPROVE_AMT,ln.STARTCONT_DATE,ln.PERIOD_PAYMENT,ln.period_payamt as PERIOD,
										ln.LAST_PERIODPAY as LAST_PERIOD,
										(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
										FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
										WHERE ln.member_no = :member_no and ln.loancontract_no = :contract_no and ln.contract_status > 0 and ln.contract_status <> 8");
	$getContract->execute([
		':member_no' => $member_no,
		':contract_no' => $contract_no
	
	]);
	while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
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
		$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
		if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
			($arrGroupContract['CONTRACT'])[] = $arrContract;
			$arrAllLoan[] = $arrGroupContract;
		}else{
			($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
		}
	}
	$type_loan =  $arrAllLoan[0]["TYPE_LOAN"];
	$contract =  $arrAllLoan[0]["CONTRACT"][0];
	
	$arrayGroupSTM = array();
	$limit = $lineLib->getLineConstant('limit_stmloan');
	$arrayResult['LIMIT_DURATION'] = $limit;
	if($lib->checkCompleteArgument(["date_start"],$dataComing)){
		$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
	}else{
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
	}
	if($lib->checkCompleteArgument(["date_end"],$dataComing)){
		$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
	}else{
		$date_now = date('Y-m-d');
	}
	//$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
	if($dataComing["channel"] == 'mobile_app'){
		$rownum = $lineLib->getLineConstant('limit_stmloan');
		if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO > ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO > 0";
		}else{
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO < 999999";
		}
	}else{
		
		$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO < 999999";
	}
	$getAccount = $conmssql->prepare("SELECT principal_balance as LOAN_BALANCE FROM lncontmaster
										WHERE contract_status = 1 and loancontract_no = :contract_no");
	$getAccount->execute([
		':contract_no' => $contract_no
	]);

	$arrayHeaderAcc["DATA_TIME"] = date('H:i');
	$getStatement = $conmssql->prepare("SELECT TOP ".$limit." lit.LOANITEMTYPE_DESC AS TYPE_DESC,lsm.OPERATE_DATE,lsm.principal_payment as PRN_PAYMENT,lsm.SEQ_NO,
										lsm.interest_payment as INT_PAYMENT,lsm.principal_balance as LOAN_BALANCE
										FROM lncontstatement lsm LEFT JOIN LNUCFLOANITEMTYPE lit
										ON lsm.LOANITEMTYPE_CODE = lit.LOANITEMTYPE_CODE
										WHERE RTRIM(lsm.loancontract_no) = :contract_no and lsm.LOANITEMTYPE_CODE <> 'AVG' and lsm.OPERATE_DATE
										BETWEEN CONVERT(varchar, :datebefore, 23) and CONVERT(varchar, :datenow, 23) ".$old_seq_no." 
										ORDER BY lsm.SEQ_NO DESC");
	$getStatement->execute([
		':contract_no' => $contract_no,
		':datebefore' => $date_before,
		':datenow' => $date_now
	]);
	while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
		$arrSTM = array();
		$arrSTM["TYPE_DESC"] = $rowStm["TYPE_DESC"];
		$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
		$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
		$arrSTM["PRN_PAYMENT"] = number_format($rowStm["PRN_PAYMENT"],2);
		$arrSTM["INT_PAYMENT"] = number_format($rowStm["INT_PAYMENT"],2);
		$arrSTM["SUM_PAYMENT"] = number_format($rowStm["INT_PAYMENT"] + $rowStm["PRN_PAYMENT"],2);
		$arrSTM["LOAN_BALANCE"] = number_format($rowStm["LOAN_BALANCE"],2);
		$arrayGroupSTM[] = $arrSTM;
	}

	if(sizeof($arrAllLoan)>0){
		$loanStatement = [];
		$loanStatement["type"] = "flex";
		$loanStatement["altText"] = "ข้อมูลเงินกู้";
		$loanStatement["contents"]["type"] = "bubble";
		$loanStatement["contents"]["direction"] = "ltr";
		$loanStatement["contents"]["body"]["type"] = "box";
		$loanStatement["contents"]["body"]["layout"] = "vertical";
		$loanStatement["contents"]["body"]["contents"][0]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][0]["text"] = ($type_loan??"ไม่พบข้อมูล");
		$loanStatement["contents"]["body"]["contents"][0]["weight"] = "bold";
		$loanStatement["contents"]["body"]["contents"][0]["size"] = "md";
		$loanStatement["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
		$loanStatement["contents"]["body"]["contents"][0]["align"] = "center";
		$loanStatement["contents"]["body"]["contents"][1]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][1]["text"] = "ข้อมูล ณ  ".$lib->convertdate(date("d-m-Y"),'D m Y')." ".date('H:i'); ;
		$loanStatement["contents"]["body"]["contents"][1]["size"] = "xxs";
		$loanStatement["contents"]["body"]["contents"][1]["align"] = "end";
		$loanStatement["contents"]["body"]["contents"][1]["wrap"] = true;
		$loanStatement["contents"]["body"]["contents"][2]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][2]["text"] = "หนี้คงเหลือ(บาท)";
		$loanStatement["contents"]["body"]["contents"][2]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][3]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][3]["text"] = ($contract["LOAN_BALANCE"]??"ไม่พบข้อมูล")." บาท";
		$loanStatement["contents"]["body"]["contents"][3]["weight"] = "bold";
		$loanStatement["contents"]["body"]["contents"][3]["size"] = "lg";
		$loanStatement["contents"]["body"]["contents"][3]["color"] = "#0EA7CA";
		$loanStatement["contents"]["body"]["contents"][3]["offsetStart"] = "20px";
		$loanStatement["contents"]["body"]["contents"][4]["type"] = "box";
		$loanStatement["contents"]["body"]["contents"][4]["layout"] = "baseline";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][0]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][0]["text"] = "เลขที่สัญญา";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][0]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][1]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][1]["text"] = ($contract["CONTRACT_NO"]??"ไม่พบข้อมูล");
		$loanStatement["contents"]["body"]["contents"][4]["contents"][1]["weight"] = "bold";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][1]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][1]["color"] = "#111111";
		$loanStatement["contents"]["body"]["contents"][4]["contents"][1]["align"] = "end";
		$loanStatement["contents"]["body"]["contents"][5]["type"] = "box";
		$loanStatement["contents"]["body"]["contents"][5]["layout"] = "baseline";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][0]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][0]["text"] = "วันที่เริ่มต้นสัญญา";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][0]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][1]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][1]["text"] = ($contract["STARTCONT_DATE"]??"ไม่พบข้อมูล");
		$loanStatement["contents"]["body"]["contents"][5]["contents"][1]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][1]["color"] = "#2068C7FF";
		$loanStatement["contents"]["body"]["contents"][5]["contents"][1]["align"] = "end";
		$loanStatement["contents"]["body"]["contents"][6]["type"] = "box";
		$loanStatement["contents"]["body"]["contents"][6]["layout"] = "baseline";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][0]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][0]["text"] = "วงเงินกู้";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][0]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][0]["color"] = "#AAAAAA";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][1]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][1]["text"] = ($contract["APPROVE_AMT"]??"ไม่พบข้อมูล")." บาท";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][1]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][1]["weight"] = "bold";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][1]["color"] = "#FF0000";
		$loanStatement["contents"]["body"]["contents"][6]["contents"][1]["align"] = "end";
		$loanStatement["contents"]["body"]["contents"][7]["type"] = "box";
		$loanStatement["contents"]["body"]["contents"][7]["layout"] = "baseline";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][0]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][0]["text"] = "งวด";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][0]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][0]["color"] = "#AAAAAA";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][1]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][1]["text"] = ($contract["PERIOD"]??"ไม่พบข้อมูล");
		$loanStatement["contents"]["body"]["contents"][7]["contents"][1]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][1]["color"] = "#111111";
		$loanStatement["contents"]["body"]["contents"][7]["contents"][1]["align"] = "end";
		$loanStatement["contents"]["body"]["contents"][8]["type"] = "box";
		$loanStatement["contents"]["body"]["contents"][8]["layout"] = "baseline";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][0]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][0]["text"] = "ชำระต่องวด";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][0]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][0]["color"] = "#AAAAAA";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][1]["type"] = "text";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][1]["text"] = ($contract["PERIOD_PAYMENT"]??"ไม่พบข้อมูล")."บาท";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][1]["size"] = "xs";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][1]["color"] = "#111111";
		$loanStatement["contents"]["body"]["contents"][8]["contents"][1]["align"] = "end";
		$loanStatement["contents"]["body"]["contents"][9]["type"] = "box";
		$loanStatement["contents"]["body"]["contents"][9]["layout"] = "vertical";
		$indexStament = 0;
		if(sizeof($arrayGroupSTM) > 0){
			foreach($arrayGroupSTM as $loanStament){
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["type"] = "box";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["layout"] = "vertical";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["type"] = "box";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["layout"] = "vertical";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["margin"] = "md";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][0]["type"] = "separator";

				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][1]["type"] = "text";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][1]["text"] = ($loanStament["TYPE_DESC"]??"ไม่พบข้อมูล");
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][1]["weight"] = "bold";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][1]["size"] = "xxs";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][1]["margin"] = "md";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][2]["type"] = "text";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][2]["text"] = ($loanStament["OPERATE_DATE"]??"ไม่พบข้อมูล");
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][2]["size"] = "xs";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][2]["color"] = "#111111";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][2]["align"] = "end";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][3]["type"] = "text";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][3]["text"] = ($loanStament["SUM_PAYMENT"]??"ไม่พบข้อมูล")." บาท";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][3]["weight"] = "bold";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][3]["size"] = "xs";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][3]["color"] = "#1885C3";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][3]["align"] = "end";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["type"] = "text";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["text"] = "หนี้คงเหลือ";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["size"] = "xxs";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["align"] = "end";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["contents"][0]["type"] = "span";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["contents"][0]["text"] = "เงินต้น: ".($loanStament["PRN_PAYMENT"]??"0.00")." บาท/";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["contents"][1]["type"] = "span";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["contents"][1]["color"] = "#AAAAAA";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][4]["contents"][1]["text"] = "ดอกเบี้ย ".($loanStament["INT_PAYMENT"]??"0.00")." บาท";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["type"] = "text";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["text"] = "หนี้คงเหลือ";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["size"] = "sm";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["align"] = "end";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["contents"][0]["type"] = "span";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["contents"][0]["text"] = "หนี้คงเหลือ : ";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["contents"][1]["type"] = "span";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["contents"][1]["weight"] = "bold";
				$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["contents"][0]["contents"][5]["contents"][1]["text"] = ($loanStament["LOAN_BALANCE"]??"ไม่พบข้อมูล")." บาท";
				$allSm=$loanStament["TYPE_DESC"];
				$indexStament++;
			}
		}else{
			$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["type"] = "text";
			$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["text"] = "ไม่พบรายการเคลื่อนไหว";
			$loanStatement["contents"]["body"]["contents"][9]["contents"][$indexStament]["align"] = "end";
		}
		$arrPostData["messages"][0] = $loanStatement;
		$arrPostData["replyToken"] = $reply_token; 
	}
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>