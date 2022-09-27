<?php
if($lineLib->checkBindAccount($user_id)){
$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	$arrGroupCredit = array();
	$arrCanCal = array();
	$arrCanReq = array();
	$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code,is_loanrequest FROM gcconstanttypeloan WHERE is_creditloan = '1' ORDER BY loantype_code ASC");
	$fetchLoanCanCal->execute();
	while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
		$salary_amt = 0;
		$fetchLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
		$fetchLoanType->execute([':loantype_code' => $rowCanCal["loantype_code"]]);
		$rowLoanType = $fetchLoanType->fetch(PDO::FETCH_ASSOC);
		$arrCredit = array();
		$maxloan_amt = 0;
		$receive_net = 0;
		$canRequest = FALSE;
		
		if(file_exists('../mobile_and_web-control/credit/calculate_loan_'.$rowCanCal["loantype_code"].'.php')){
			include('../mobile_and_web-control/credit/calculate_loan_'.$rowCanCal["loantype_code"].'.php');
		}else{
			include('../mobile_and_web-control/credit/calculate_loan_etc.php');
		}
		if($canRequest === TRUE){
			$canRequest = $rowCanCal["is_loanrequest"] == '1' ? TRUE : FALSE;
			$CheckIsReq = $conmysql->prepare("SELECT reqloan_doc,req_status
														FROM gcreqloan WHERE loantype_code = :loantype_code and member_no = :member_no and req_status NOT IN('-9','9')");
			$CheckIsReq->execute([
				':loantype_code' => $rowCanCal["loantype_code"],
				':member_no' => $member_no
			]);
			if($CheckIsReq->rowCount() > 0 || $maxloan_amt <= 0){
				$canRequest = FALSE;
			}
		}
		$arrCredit["ALLOW_REQUEST"] = $canRequest;
		$arrCredit["LOANTYPE_CODE"] = $rowCanCal["loantype_code"];
		$arrCredit["LOANTYPE_DESC"] = $rowLoanType["LOANTYPE_DESC"];
		$arrCredit["salary_amt"] = $salary_amt;
		$arrCredit["MAXLOAN_AMT"] = number_format($maxloan_amt,2);
		$arrCredit["RECEIVE_NET"] = number_format($receive_net,2);

		$arrGroupCredit[] = $arrCredit;
	}
	if(sizeof($arrGroupCredit)>0){
		$ListDatas  = array_chunk($arrGroupCredit, 12);
		$indexListDatas = 0;
		foreach($ListDatas as $rowListDatas){
			$loanCreaditData = array();
			$loanCreaditData["type"] = "flex";
			$loanCreaditData["altText"] = "สิทธิ์กู้โดยประมาณ".$rowMaxloan;
			$loanCreaditData["contents"]["type"] = "carousel";
			$indexLoanCredit = 0;
			foreach($rowListDatas as $rowLoanCredit){
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
				$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["text"] = ($rowLoanCredit["RECEIVE_NET"]??'-').' บาท';
				$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["weight"] = "bold";
				$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["size"] = "xs";
				$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["color"] = "#EB6209";
				$loanCreaditData["contents"]["contents"][$indexLoanCredit]["body"]["contents"][3]["contents"][1]["align"] = "end";
				$indexLoanCredit++;
			}
			$arrPostData["messages"][$indexListDatas] = $loanCreaditData;
			$indexListDatas++;
		}
		$arrPostData["replyToken"] = $reply_token;

	}else{
		$messageResponse = "ไม่พบข้อมูลสิทธิ์กู้โดยประมาณ";
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