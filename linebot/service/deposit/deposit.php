<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	$fetchDeposit = $conmssql->prepare("SELECT DISTINCT  dp.DEPTTYPE_CODE,dt.DEPTTYPE_DESC
										FROM dpdeptmaster dp
										LEFT JOIN DPDEPTTYPE dt ON dp.DEPTTYPE_CODE = dt.DEPTTYPE_CODE 
										WHERE   member_no = :member_no  
											AND deptclose_status <> 1");
	$fetchDeposit->execute([
			':member_no' => $member_no 
	]);
	$getSumdepositAll = $conmssql->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status <> 1");
	$getSumdepositAll->execute([':member_no' => $member_no]);
	$rowSumAllbalance = $getSumdepositAll->fetch(PDO::FETCH_ASSOC);
	
	$getTotalAllAccount = $conmssql->prepare("SELECT COUNT(deptaccount_no) as TOTALACCOUNT FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status <> 1;");
	$getTotalAllAccount->execute([':member_no' => $member_no]);
	$rowTotalAllAccount = $getTotalAllAccount->fetch(PDO::FETCH_ASSOC);
	
	$groupDepositData = array();
	while($rowDeposit = $fetchDeposit->fetch(PDO::FETCH_ASSOC)){
		$getSumDeposit =  $conmssql->prepare("SELECT SUM(prncbal) as SUM_BALANCE FROM dpdeptmaster WHERE member_no = :member_no  and DEPTTYPE_CODE = :depttype_code  and deptclose_status <> 1");
		$getSumDeposit->execute([
			':member_no' => $member_no,
			':depttype_code' => $rowDeposit["DEPTTYPE_CODE"]
		]);
		$arrSumDeposit = $getSumDeposit->fetch(PDO::FETCH_ASSOC);
		$getTotalAccount = $conmssql->prepare("SELECT COUNT(deptaccount_no) as TOTALACCOUNT FROM dpdeptmaster WHERE member_no = :member_no and DEPTTYPE_CODE = :depttype_code and deptclose_status <> 1;");
		$getTotalAccount->execute([
			':member_no' => $member_no,
			':depttype_code' => $rowDeposit["DEPTTYPE_CODE"]
		]);
		$rowTotalAccount = $getTotalAccount->fetch(PDO::FETCH_ASSOC);
		
		
		$arrDepositData["DEPTTYPE_CODE"] = $rowDeposit["DEPTTYPE_CODE"];
		$arrDepositData["DEPTTYPE_DESC"] = $rowDeposit["DEPTTYPE_DESC"];
		$arrDepositData["SUM_BALANCE"] = number_format($arrSumDeposit["SUM_BALANCE"],2);
		$arrDepositData["TOTAL_ACCOUNT"] = $rowTotalAccount["TOTALACCOUNT"];
		$groupDepositData[] = $arrDepositData;
	}
	
	if(sizeof($groupDepositData) > 0){
		$depositData = array();
		$depositData["type"] = "flex";
		$depositData["altText"] = "เงินฝาก";
		$depositData["contents"]["type"] = "carousel";
		$depositData["contents"]["contents"][0]["type"] = "bubble";
		$depositData["contents"]["contents"][0]["body"]["type"] = "box";
		$depositData["contents"]["contents"][0]["body"]["layout"] = "vertical";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["type"] = "box";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["layout"] = "baseline";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["margin"] = "xl";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["type"] = "text";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["text"] = "เงินฝาก";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["color"] = ($themeColor??"#000000");
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["weight"] = "bold";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["size"] = "xl";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["type"] = "text";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["text"] = "จำนวน";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["size"] = "sm";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["align"] = "end";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "span";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = "จำนวน ";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "span";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["text"] = ($rowTotalAllAccount["TOTALACCOUNT"]??"-");
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["color"] = "#35B84B";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["weight"] = "bold";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][2]["type"] = "span";
		$depositData["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][2]["text"] = " บัญชี";
		$depositData["contents"]["contents"][0]["body"]["contents"][1]["type"] = "text";
		$depositData["contents"]["contents"][0]["body"]["contents"][1]["text"] = "ยอดรวมทุกบัญชี";
		$depositData["contents"]["contents"][0]["body"]["contents"][1]["weight"] = "bold";
		$depositData["contents"]["contents"][0]["body"]["contents"][1]["size"] = "md";
		$depositData["contents"]["contents"][0]["body"]["contents"][1]["color"] = "#FFFFFFFF";
		$depositData["contents"]["contents"][0]["body"]["contents"][1]["align"] = "center";
		$depositData["contents"]["contents"][0]["body"]["contents"][2]["type"] = "text";
		$depositData["contents"]["contents"][0]["body"]["contents"][2]["text"] = "ยอดรวมทุกบัญชี";
		$depositData["contents"]["contents"][0]["body"]["contents"][2]["weight"] = "bold";
		$depositData["contents"]["contents"][0]["body"]["contents"][2]["size"] = "md";
		$depositData["contents"]["contents"][0]["body"]["contents"][2]["align"] = "end";
		$depositData["contents"]["contents"][0]["body"]["contents"][3]["type"] = "text";
		$depositData["contents"]["contents"][0]["body"]["contents"][3]["text"] = (number_format($rowSumAllbalance["SUM_BALANCE"])??'-').' บาท';
		$depositData["contents"]["contents"][0]["body"]["contents"][3]["weight"] = "bold";
		$depositData["contents"]["contents"][0]["body"]["contents"][3]["size"] = "lg";
		$depositData["contents"]["contents"][0]["body"]["contents"][3]["color"] = "#35B84B";
		$depositData["contents"]["contents"][0]["body"]["contents"][3]["align"] = "end";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["type"] = "image";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["url"] = "https://cdn.thaicoop.co/icon/wallet.png";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["align"] = "start";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["size"] = "xs";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["position"] = "absolute";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["offsetTop"] = "67px";
		$depositData["contents"]["contents"][0]["body"]["contents"][4]["offsetStart"] = "30px";
		
		$indexDeposit = 1;
		foreach($groupDepositData as $deposit){
			$depositData["contents"]["contents"][$indexDeposit]["type"] = "bubble";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["type"] = "box";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["layout"] = "vertical";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["spacing"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][0]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][0]["text"] = ($deposit["DEPTTYPE_DESC"]??'-');
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][0]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][0]["size"] = "md";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][0]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["type"] = "box";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["layout"] = "baseline";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][0]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][0]["text"] = "ยอดรวม :";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][0]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][0]["size"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][0]["flex"] = 0;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][0]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["text"] = ($deposit["SUM_BALANCE"]??'0.00');
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["size"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["color"] = "#35B84B";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["flex"] = 0;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["align"] = "start";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["gravity"] = "top";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["margin"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][1]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["text"] = "บาท";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["size"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["flex"] = 0;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["margin"] = "md";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][1]["contents"][2]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["type"] = "box";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["layout"] = "baseline";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][0]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][0]["text"] = "จำนวนบัญชี ";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][0]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][0]["size"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][0]["flex"] = 0;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][0]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["text"] = ($deposit["TOTAL_ACCOUNT"]??'-');
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["size"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["color"] = "#35B84B";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["flex"] = 0;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["align"] = "start";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["gravity"] = "top";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["margin"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][1]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["type"] = "text";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["text"] = "บัญชี";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["weight"] = "bold";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["size"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["flex"] = 0;
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["margin"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["body"]["contents"][2]["contents"][2]["wrap"] = true;
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["type"] = "box";
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["layout"] = "vertical";
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["spacing"] = "sm";
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["contents"][0]["type"] = "button";
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["contents"][0]["action"]["type"] = "message";
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["contents"][0]["action"]["label"] = "ดูรายละเอียด";
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["contents"][0]["action"]["text"] = "ประเภทเงินฝาก:".($deposit["DEPTTYPE_DESC"]??'-');
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["contents"][0]["color"] = ($themeColor??"#000000");
			$depositData["contents"]["contents"][$indexDeposit]["footer"]["contents"][0]["style"] = "primary";
			$indexDeposit++;
		}
		$arrPostData["messages"][0] = $depositData;
		$arrPostData["replyToken"] = $reply_token; 
	
	}else{
		$messageResponse = "ไม่พบ ข้อมูลเงินฝากของท่าน";
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