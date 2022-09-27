<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$getUcollwho = $conoracle->prepare("SELECT
											COUNT(LCC.LOANCONTRACT_NO) as LIST
										FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
										LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
										LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
										LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
										WHERE
											LCM.CONTRACT_STATUS > 0 and LCM.CONTRACT_STATUS <> 8
										AND LCC.LOANCOLLTYPE_CODE = '01'
										AND LCC.REF_COLLNO = :member_no");
	$getUcollwho->execute([':member_no' => $member_no]);
	$rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC);
	$totlalUcollWho = $rowUcollwho["LIST"];
	
	$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
                      FROM lncontmaster lnm LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no
                      and lnm.contract_status > 0 and lnm.contract_status <> 8
                      GROUP BY lnm.loancontract_no,NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC,lnm.principal_balance");
	$getWhocollu->execute([':member_no' => $member_no]);
	$groupWhocollu = array();
	while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
		$groupWhocollu[] = $rowWhocollu; 
	}
	$totalWhocollu = count($groupWhocollu)??0;
	
	$guaranteeData = [];
	$guaranteeData["type"] = "flex";
	$guaranteeData["altText"] = "ภาระค้ำประกัน";
	$guaranteeData["contents"]["type"] = "bubble";
	$guaranteeData["contents"]["direction"] = "ltr";
	$guaranteeData["contents"]["body"]["type"] = "box";
	$guaranteeData["contents"]["body"]["layout"] = "vertical";
	$guaranteeData["contents"]["body"]["contents"][0]["type"] = "text";
	$guaranteeData["contents"]["body"]["contents"][0]["text"] = "ภาระค้ำประกัน";
	$guaranteeData["contents"]["body"]["contents"][0]["size"] = "xl";
	$guaranteeData["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
	$guaranteeData["contents"]["body"]["contents"][0]["align"] = "start";
	$guaranteeData["contents"]["body"]["contents"][0]["offsetStart"] = "50px";
	
	$guaranteeData["contents"]["body"]["contents"][1]["type"] = "image";
	$guaranteeData["contents"]["body"]["contents"][1]["url"] = "https://cdn.thaicoop.co/icon/guaranteeInfo.png";
	$guaranteeData["contents"]["body"]["contents"][1]["size"] = "xxs";
	$guaranteeData["contents"]["body"]["contents"][1]["position"] = "absolute";
	$guaranteeData["contents"]["body"]["contents"][1]["offsetTop"] = "20px";
	$guaranteeData["contents"]["body"]["contents"][1]["offsetStart"] = "20px";
	
	$guaranteeData["contents"]["body"]["contents"][2]["type"] = "box";
	$guaranteeData["contents"]["body"]["contents"][2]["layout"] = "vertical";
	$guaranteeData["contents"]["body"]["contents"][2]["margin"] = "xl";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["text"] = "คุณค้ำใคร";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][0]["type"] = "span";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][0]["text"] = "ภาระค้ำประกันของคุณ";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][1]["type"] = "span";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][1]["text"] = " ".$totlalUcollWho." ";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][1]["color"] = "#35B84B";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][1]["weight"] = "bold";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][2]["type"] = "span";
	$guaranteeData["contents"]["body"]["contents"][2]["contents"][0]["contents"][2]["text"] = "สัญญา";
	if($totlalUcollWho == 0){
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["type"] = "filler";
	}else{
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["type"] = "button";
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["action"]["type"] = "message";
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["action"]["label"] = "ดูข้อมูลภาระค้ำประกัน";
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["action"]["text"] = "ข้อมูลภาระค้ำประกันของฉัน";
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["color"] = "#E3519D";
		$guaranteeData["contents"]["body"]["contents"][2]["contents"][1]["style"] = "primary";
	}
	
	$guaranteeData["contents"]["body"]["contents"][3]["type"] = "box";
	$guaranteeData["contents"]["body"]["contents"][3]["layout"] = "vertical";
	$guaranteeData["contents"]["body"]["contents"][3]["margin"] = "xl";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["type"] = "text";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["text"] = "ใครค้ำคุณ";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][0]["type"] = "span";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][0]["text"] = "ใครค้ำคุณ";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][1]["type"] = "span";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][1]["text"] = " ".$totalWhocollu." ";;
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][1]["color"] = "#35B84B";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][1]["weight"] = "bold";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][2]["type"] = "span";
	$guaranteeData["contents"]["body"]["contents"][3]["contents"][0]["contents"][2]["text"] = "สัญญา";
	if($totalWhocollu == 0){
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["type"] = "filler";
	}else{
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["type"] = "button";
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["action"]["type"] = "message";
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["action"]["label"] = "ดูข้อมูลใครค้ำคุณ";
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["action"]["text"] = "ข้อมูลใครค้ำคุณ";
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["color"] = "#1885C3";
		$guaranteeData["contents"]["body"]["contents"][3]["contents"][1]["style"] = "primary";
	}
	$arrPostData["messages"][0] = $guaranteeData;
	$arrPostData["replyToken"] = $reply_token;
	//$arrPostData["replyToken"] = $groupWhocollu;

}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>