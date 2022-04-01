<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	$arrGrpReq = array();
	
	$fetchReqLoan = $conmysql->prepare("SELECT reqloan_doc,loantype_code,request_amt,period_payment,period,req_status,loanpermit_amt,
															diff_old_contract,receive_net,salary_img,citizen_img,remark,approve_date,contractdoc_url
															FROM gcreqloan WHERE member_no = :member_no ORDER BY update_date DESC");
	$fetchReqLoan->execute([':member_no' => $data]);
	while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
		$getLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
		$getLoanType->execute([':loantype_code' => $rowReqLoan["loantype_code"]]);
		$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
		$arrayReq = array();
		$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
		$arrayReq["REQLOAN_DOC"] = $rowReqLoan["reqloan_doc"];
		$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["loantype_code"];
		$arrayReq["REQUEST_AMT"] = $rowReqLoan["request_amt"];
		//$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
		$arrayReq["PERIOD"] = $rowReqLoan["period"];
		$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
		$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["req_status"]][0][$lang_locale];
		$arrayReq["LOANPERMIT_AMT"] = number_format($rowReqLoan["loanpermit_amt"],2);
		$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
		$arrayReq["RECEIVE_NET"] = number_format($rowReqLoan["receive_net"],2);
		$arrayReq["SALARY_IMG"] = $rowReqLoan["salary_img"];
		$arrayReq["CITIZEN_IMG"] = $rowReqLoan["citizen_img"];
		$arrayReq["REMARK"] = $rowReqLoan["remark"];
		$arrayReq["CONTRACTDOC_URL"] = $rowReqLoan["contractdoc_url"];
		$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
		$arrGrpReq[] = $arrayReq;
	}
	
	
	if(sizeof($arrGrpReq)>0){
		$datas = [];
		$datas["type"] = "flex";
		$datas["altText"] = "ข้อมูลใบคำขอกู้ออนไลน์";
		$datas["contents"]["type"] = "carousel";
		$indexContents = 0;
		foreach($arrGrpReq as $rowGrap){
			if($rowGrap["REQ_STATUS"] == "8" ){
				$statusColor = "#F39C12";
			}else if($rowGrap["REQ_STATUS"] == "1"){
				$statusColor = "#1E8449";
			}else if($rowGrap["REQ_STATUS"] == "-9"){
				$statusColor = "#FF0000";
			}else if($rowGrap["REQ_STATUS"] == "7"){
				$statusColor = "#D35400";
			}else{
				$statusColor = "#000000";
			}
			$datas["contents"]["contents"][$indexContents]["type"] = "bubble";
			$datas["contents"]["contents"][$indexContents]["direction"] = "ltr";
			$datas["contents"]["contents"][$indexContents]["header"]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["header"]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["header"]["spacing"] = "lg";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][0]["text"] = "เลขที่คำขอ";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][0]["size"] = "md";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][0]["color"] = $themeColor;
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][1]["text"] = ($rowGrap["REQLOAN_DOC"]??'-');
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][1]["size"] = "md";
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][1]["color"] = $themeColor;
			$datas["contents"]["contents"][$indexContents]["header"]["contents"][0]["contents"][1]["align"] = "end";
			if(isset($rowGrap["APPROVE_DATE"]) && $rowGrap["APPROVE_DATE"] != null){
				$datas["contents"]["contents"][$indexContents]["header"]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$indexContents]["header"]["contents"][1]["text"] = "วันที่อนุมัติ : " .($rowGrap["APPROVE_DATE"]??'-');
				$datas["contents"]["contents"][$indexContents]["header"]["contents"][1]["size"] = "md";
				$datas["contents"]["contents"][$indexContents]["header"]["contents"][1]["color"] = "#1E8449";
				$datas["contents"]["contents"][$indexContents]["header"]["contents"][2]["type"] = "separator";
			}else{
				$datas["contents"]["contents"][$indexContents]["header"]["contents"][1]["type"] = "separator";
			}
			
			$datas["contents"]["contents"][$indexContents]["body"]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["spacing"] = "none";
			$datas["contents"]["contents"][$indexContents]["body"]["margin"] = "none";
			$datas["contents"]["contents"][$indexContents]["body"]["paddingTop"] = "0px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["offsetStart"] = "110px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["width"] = "150px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["height"] = "35px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["justifyContent"] = "center";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["alignItems"] = "center";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["backgroundColor"] = $themeColor;
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["cornerRadius"] = "10px";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["action"]["type"] = "uri";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["action"]["label"] = "ใบเสร็จ";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["action"]["uri"] = ($rowGrap["CONTRACTDOC_URL"]?? "https://proxy.thaicoop.co/RYTCOOP-TEST/resource/pdf/keeping_monthly/emp.pdf");
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["text"] = "เรียกดูใบคำขอ";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["size"] = "md";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["color"] = "#FFFFFFFF";

			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["margin"] = "md";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][0]["text"] = "สถานะคำขอ:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][1]["text"] = ($rowGrap["REQ_STATUS_DESC"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][1]["color"] = $statusColor;
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][1]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][2]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][2]["text"] = "ประเภทเงินกู้:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][2]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][3]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][3]["text"] = ($rowGrap["LOANTYPE_DESC"]??'-');
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][3]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][3]["color"] = "#342FD7FF";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][3]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][0]["text"] = "สิทธิ์กู้สุงสุด:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][1]["text"] = ($rowGrap["LOANPERMIT_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][4]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][0]["text"] = "จำนวนเงินที่ขอกู้:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][1]["text"] = ($rowGrap["REQUEST_AMT"]??'-').' บาท';
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][5]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][0]["text"] = "จำนวนงวด:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][1]["text"] = ($rowGrap["PERIOD"]??'-')." งวด";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][6]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][0]["text"] = "หักกลบหนี้เดิม:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][1]["text"] = "0.00 บาท";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][7]["contents"][1]["align"] = "end";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["layout"] = "horizontal";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][0]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][0]["text"] = "จำนวนที่จะได้รับ:";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][0]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][1]["type"] = "text";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][1]["text"] = ($rowGrap["RECEIVE_NET"]??'-')." บาท";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][1]["weight"] = "bold";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][1]["size"] = "sm";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][1]["color"] = "#35B84B";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][8]["contents"][1]["align"] = "end";
			
			
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["layout"] = "vertical";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["margin"] = "xs";
			if($rowGrap["SALARY_IMG"] == null || $rowGrap["CITIZEN_IMG"] == null){
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][0]["type"] = "filler";
			}else{
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][0]["text"] = "หลักฐานสำรับร้องขอ";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][0]["size"] = "sm";
			}
		
			
			
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["type"] = "box";
			$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["layout"] = "horizontal";
			
	
			
			if(isset($rowGrap["SALARY_IMG"])){
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][0]["type"] = "image";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][0]["url"] = $rowGrap["SALARY_IMG"];
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][0]["action"]["type"] = "uri";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][0]["action"]["label"] = "label";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][0]["action"]["uri"] = $rowGrap["SALARY_IMG"];
			}else{
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][0]["type"] = "filler";
			}
			if(isset($rowGrap["CITIZEN_IMG"])){
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][1]["type"] = "image";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][1]["url"] = $rowGrap["CITIZEN_IMG"];
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][1]["action"]["type"] = "uri";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][1]["action"]["label"] = "label";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][1]["action"]["uri"] = $rowGrap["CITIZEN_IMG"];
			}else{
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][9]["contents"][1]["contents"][1]["type"] = "filler";
			}
			
			
			if(isset($rowGrap["REMARK"]) && $rowGrap["REMARK"] != null){
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["type"] = "box";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["layout"] = "vertical";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["paddingAll"] = "5px";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["backgroundColor"] = "#EEEEAA";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][0]["text"] = "หมายเหตุ:";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][0]["weight"] = "bold";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][0]["size"] = "sm";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][1]["type"] = "text";
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][1]["text"] = ($rowGrap["REMARK"]??'-');
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["contents"][1]["wrap"] = true;
			}else{
				$datas["contents"]["contents"][$indexContents]["body"]["contents"][10]["type"] = "filler";
			}
			
			$indexContents++;
			
		}
		$arrPostData["messages"][0] = $datas;
		$arrPostData["replyToken"] = $reply_token;
		

	}else{
		
		$messageResponse = "ไม่พบใบคำขอกู้ออนไลน์ของท่าน";
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