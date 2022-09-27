<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrGroupBNF = array();
	$getBeneficiary = $conoracle->prepare("SELECT mg.gain_name,mg.gain_surname,mg.gain_addr as GAIN_ADDR,mc.gain_concern,mg.remark
												FROM mbgainmaster mg LEFT JOIN mbucfgainconcern mc ON mg.gain_relation = mc.concern_code
												WHERE mg.member_no = :member_no");
	$getBeneficiary->execute([':member_no' => $member_no]);
	while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
		$arrBenefit = array();
		$arrBenefit["FULL_NAME"] = $rowBenefit["PRENAME_SHORT"].$rowBenefit["GAIN_NAME"].' '.$rowBenefit["GAIN_SURNAME"];
		if(isset($rowBenefit["GAIN_ADDR"])){
			$arrBenefit["ADDRESS"] = preg_replace("/ {2,}/", " ", $rowBenefit["GAIN_ADDR"]);
		}
		$arrBenefit["RELATION"] = $rowBenefit["GAIN_CONCERN"];
		$arrBenefit["PERCENT_TEXT"] = $rowBenefit["REMARK"]??'แบ่งให้เท่า ๆ กัน';
		$arrBenefit["PERCENT"] = filter_var(number_format($rowBenefit["GAIN_PERCENT"],0), FILTER_SANITIZE_NUMBER_INT);
		$arrGroupBNF[] = $arrBenefit;
	}
	
	$beneficiaryData = array();
	
	if(sizeof($arrGroupBNF)>0){
		$beneficiaryData["type"] = "flex";
		$beneficiaryData["altText"] = "ผู้รับผลประโยชน์";
		$beneficiaryData["contents"]["type"] = "carousel";
		$indexBeneficiary = 0;
		$percent = 100;
		foreach($arrGroupBNF as $rowBeneficiaryData){
			if($rowBeneficiaryData["PERCENT"] == "0"){
				$percentRe = $percent/(sizeof($arrGroupBNF));
			}else{
				$percentRe = $percent-(intval($rowBeneficiaryData["PERCENT"]));
			}
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["type"] = "bubble";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["direction"] = "ltr";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["type"] = "box";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["layout"] = "vertical";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["type"] = "box";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["layout"] = "vertical";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][0]["type"] = "text";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][0]["text"] = ($rowBeneficiaryData["FULL_NAME"]??'-');
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][0]["weight"] = "bold";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][0]["size"] = "md";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][0]["color"] = "#000000";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][0]["align"] = "start";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["type"] = "text";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["text"] = "text";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["weight"] = "bold";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["size"] = "xs";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["color"] = "#000000";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["offsetStart"] = "10px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "span";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = "ความสัมพันธ์  : ";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["contents"][0]["color"] = "#AAAAAA";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "span";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][1]["contents"][1]["text"] = ($rowBeneficiaryData["RELATION"]??'-');
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["type"] = "box";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["layout"] = "horizontal";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["borderWidth"] = "1px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["type"] = "text";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["text"] = "สัดส่วน";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["size"] = "xs";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["contents"][0]["type"] = "span";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["contents"][0]["text"] = "สัดส่วน : ";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["contents"][0]["color"] = "#AAAAAA";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["contents"][1]["type"] = "span";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["contents"][1]["text"] = ($rowBeneficiaryData["PERCENT_TEXT"]??'-');
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["align"] = "start";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][0]["offsetStart"] = "10px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][1]["type"] = "text";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][1]["text"] = $percentRe."%";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][1]["weight"] = "bold";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][1]["color"] = "#35B84B";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][2]["contents"][1]["align"] = "end";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["type"] = "box";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["layout"] = "vertical";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["type"] = "box";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["layout"] = "vertical";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["margin"] = "md";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["height"] = "20px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["borderWidth"] = "2px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["borderColor"] = "#35B84B";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["cornerRadius"] = "10px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["contents"][0]["type"] = "box";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["contents"][0]["layout"] = "vertical";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["contents"][0]["height"] = "20px";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["contents"][0]["width"] = $percentRe.'%';
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["contents"][0]["backgroundColor"] = "#35B84B";
			$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][3]["contents"][0]["contents"][0]["contents"][0]["type"] = "filler";
			
			if(isset($rowBeneficiaryData["ADDRESS"]) && $rowBeneficiaryData["ADDRESS"] !=""){
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][4]["type"] = "separator";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][4]["margin"] = "md";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["type"] = "box";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["layout"] = "baseline";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["margin"] = "md";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["type"] = "text";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["text"] = "ที่อยู่";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["weight"] = "bold";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["size"] = "sm";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["color"] = "#1885C3";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["align"] = "start";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][5]["contents"][0]["wrap"] = true;
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["type"] = "box";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["layout"] = "vertical";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["paddingStart"] = "10px";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["contents"][0]["type"] = "text";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["contents"][0]["text"] = ($rowBeneficiaryData["ADDRESS"]??'-');
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["contents"][0]["size"] = "xs";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["contents"][0]["color"] = "#000000";
				$beneficiaryData["contents"]["contents"][$indexBeneficiary]["body"]["contents"][0]["contents"][6]["contents"][0]["wrap"] = true;
			}
			
			
			$indexBeneficiary++;
		}
		$arrPostData["messages"][0] = $beneficiaryData;	
		$arrPostData["replyToken"] = $reply_token;
		 
	
	}else{
		$messageResponse = "ไม่พบข้อมูลผู้รับผลประโยชน์";
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