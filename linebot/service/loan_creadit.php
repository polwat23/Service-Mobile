<?php
if($lineLib->checkBindAccount($user_id)){
	$fetchMember_no = $conmysql->prepare("SELECT  member_no
										FROM gcmemberaccount
										WHERE line_token =:line_token");
	$fetchMember_no->execute([
			':line_token' => $user_id 
		]);
	$data = $fetchMember_no->fetch(PDO::FETCH_ASSOC);
	$member_no = $configAS[$data["member_no"]] ?? $data["member_no"];
	
	$arrGroupCredit = array();
	$arrayLoantype = array();
	$getLoantypeCredit = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_creditloan = '1'");
	$getLoantypeCredit->execute();
	while($rowLoanType = $getLoantypeCredit->fetch(PDO::FETCH_ASSOC)){
		$arrayLoantype[] = "'".$rowLoanType["loantype_code"]."'";
	}
	$fetchCredit = $conmssql->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt as MAXLOAN_AMT,LT.loantype_code as LOANTYPE_CODE,
										(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (ISNULL(mb.salary_amount,15000)*lc.multiple_salary ) AS CREDIT_AMT
										FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,
										shsharemaster sm LEFT JOIN mbmembmaster mb ON sm.member_no = mb.member_no,shsharetype sh
										WHERE mb.member_no = :member_no AND sm.SHAREMASTER_STATUS = '1' AND LT.LOANGROUP_CODE IN ( '01','02' )
										AND LT.LOANTYPE_CODE IN (".implode(",",$arrayLoantype).")
										AND (CASE WHEN DATEDIFF(dd, EOMONTH(mb.member_date), EOMONTH(getdate())) = 0 THEN 0
										ELSE DATEDIFF(mm, mb.member_date, getdate()) - 1 END) BETWEEN lc.startmember_time AND lc.endmember_time
										AND sm.sharestk_amt*sh.unitshare_value BETWEEN lc.startshare_amt AND lc.endshare_amt
										AND ISNULL(mb.salary_amount,15000) BETWEEN lc.startsalary_amt AND lc.endsalary_amt
										GROUP BY LT.loantype_code,lt.loantype_desc,lc.maxloan_amt,(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (ISNULL(mb.salary_amount,15000)*lc.multiple_salary)");
	$fetchCredit->execute([':member_no' => $member_no]);
	while($rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC)){
		$arrCredit = array();
		if($rowCredit["CREDIT_AMT"] > $rowCredit["MAXLOAN_AMT"]){
			$loan_amt = number_format($rowCredit["MAXLOAN_AMT"],2);
		}else{
			$loan_amt = number_format($rowCredit["CREDIT_AMT"],2);
		}
		$arrCredit["LOANTYPE_DESC"] = $rowCredit["LOANTYPE_DESC"];
		$arrCredit["LOANTYPE_CODE"] = $rowCredit["LOANTYPE_CODE"];
		$arrCredit['LOAN_PERMIT_AMT'] = $loan_amt ?? 0;
		$arrCredit['MAXLOAN_AMT'] = $loan_amt ?? 0;
		$arrCredit["OLD_CONTRACT"] = [];
		$arrGroupCredit[] = $arrCredit;
	}
	


	if(sizeof($arrGroupCredit)>0){
		$loanCreaditData = array();
		$loanCreaditData["type"] = "flex";
		$loanCreaditData["altText"] = "สิทธิ์กู้โดยประมาณ";
		$loanCreaditData["contents"]["type"] = "carousel";
		
		$indexLoanCredit = 0;
		foreach($arrGroupCredit as $rowLoanCredit){
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["type"] = "bubble";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["direction"] = "ltr";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["type"] = "box";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["layout"] = "vertical";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][0]["type"] = "text";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][0]["text"] = ($rowLoanCredit["LOANTYPE_DESC"]??'-');
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][0]["weight"] = "bold";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][0]["size"] = "sm";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][0]["align"] = "center";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][1]["type"] = "separator";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][1]["margin"] = "md";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["type"] = "box";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["layout"] = "horizontal";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["height"] = "30px";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["backgroundColor"] = "#EEEEEE";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["alignItems"] = "center";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][0]["type"] = "text";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][0]["text"] = "สิทธิ์กู้สูงสุด";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][0]["size"] = "xs";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][0]["color"] = "#000000";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][1]["type"] = "text";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][1]["text"] = ($rowLoanCredit["MAXLOAN_AMT"]??'-').' บาท';
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][1]["weight"] = "bold";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][1]["size"] = "xs";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][1]["color"] = "#0938A4";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][2]["contents"][1]["align"] = "end";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["type"] = "box";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["layout"] = "horizontal";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["margin"] = "md";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][0]["type"] = "text";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][0]["text"] = "ประมาณการรับเงินสุทธิ์ :";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][0]["size"] = "xs";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["type"] = "text";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["text"] = ($rowLoanCredit["MAXLOAN_AMT"]??'-').' บาท';
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["weight"] = "bold";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["size"] = "xs";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["color"] = "#EB6209";
			$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["align"] = "end";
			$indexLoanCredit++;
		}
		
		

	
	
		
		$arrPostData["messages"][0] = $loanCreaditData;
		$arrPostData["replyToken"] = $reply_token; 
		//$arrPostData["replyToken"] = $arrGroupCredit;
	
	}else{
		$messageResponse = "ไม่พบข้อมูลสิทธิ์กู้โดยประมาณ";
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