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
	
	$arrDataWC = array();
	$getCremation = $conmssql->prepare("SELECT CASE WHEN wftype_code = '1' then 'สสธท.' else 
										CASE when wftype_code = '2' then 'กสธท.ล้านที่ 2' else
										CASE when wftype_code = '3' then 'กสธท.ล้านที่ 3' else 'สส.ชสอ' end end end as WFTYPE_DESC,
										MEMB_NAME,CARD_PERSON,WFMEMBER_NO
										FROM WFCOOPMASTER WHERE member_no = :member_no ORDER BY SEQ_NO");
	$getCremation->execute([':member_no' => $member_no]);
	while($rowCremation = $getCremation->fetch(PDO::FETCH_ASSOC)){
		$arrCremation = array();
		$arrayOther[0]["LABEL"] = "เลขบัตรประจำตัวประชาชน";
		$arrayOther[0]["VALUE"] = $rowCremation["CARD_PERSON"];
		$arrayOther[1]["LABEL"] = "เลขทะเบียน";
		$arrayOther[1]["VALUE"] = $rowCremation["WFMEMBER_NO"];
		$arrPerson["NAME"] = $rowCremation["MEMB_NAME"];
		$arrCremation["PERSON"][] = $arrPerson;
		$arrCremation["OTHER_INFO"] = $arrayOther;
		$arrCremation["CREMATION_TYPE"] = $rowCremation["WFTYPE_DESC"];
		$arrCremation["CARD_COLOR"] = "#FF9966";
		$arrDataWC[] = $arrCremation;
	}
	if(sizeof($arrDataWC)>0){
		$cremationData = array();
		$cremationData["type"] = "flex";
		$cremationData["altText"] = "ฌาปนกิจ";
		$cremationData["contents"]["type"] = "carousel";
		$indexCremation = 0;
		foreach($arrDataWC as $rowCremation){
			$cremationData["contents"]["contents"][$indexCremation]["type"] = "bubble";
			$cremationData["contents"]["contents"][$indexCremation]["direction"] = "ltr";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["type"] = "box";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["layout"] = "vertical";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["type"] = "text";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["text"] = ($rowCremation["CREMATION_TYPE"]??'-');
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["weight"] = "bold";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["size"] = "lg";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["color"] = ($rowCremation["CARD_COLOR"]??'#E3519D');
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["align"] = "center";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][0]["margin"] = "none";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][1]["type"] = "separator";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][1]["margin"] = "md";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["type"] = "box";
			$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["layout"] = "vertical";
			if(sizeof($rowCremation["OTHER_INFO"])>0){
				$indexOtherInfo = 0;
				foreach($rowCremation["OTHER_INFO"] as $rowOtherInfo){
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["type"] = "box";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["layout"] = "vertical";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][0]["type"] = "text";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][0]["text"] = ($rowOtherInfo["LABEL"]??'-'); 
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][0]["size"] = "sm";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][0]["color"] = "#000000";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][0]["margin"] = "lg";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][1]["type"] = "text";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][1]["text"] = ($rowOtherInfo["VALUE"]??'-');
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][1]["weight"] = "bold";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][1]["size"] = "sm";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][1]["color"] = "#000000";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][$indexOtherInfo]["contents"][1]["align"] = "end";
					$indexOtherInfo++;
				}
			}else{
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][2]["contents"][0]["type"] = "filler";
			}
			if(sizeof($rowCremation["PERSON"])>0){
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][3]["type"] = "box";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][3]["layout"] = "vertical";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][3]["margin"] = "md";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][3]["height"] = "3px";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][3]["backgroundColor"] = "#AAAAAA";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][3]["contents"][0]["type"] = "filler";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][4]["type"] = "text";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][4]["text"] = "ผู้รับผลประโยนน์";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][4]["size"] = "sm";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][4]["color"] = "#0938A4";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][4]["margin"] = "md";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["type"] = "box";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["layout"] = "vertical";
				$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["paddingStart"] = "20px";
				$indexPerson = 0;
				foreach($rowCremation["PERSON"] as $rowPerson){
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["contents"][$indexPerson]["type"] = "text";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["contents"][$indexPerson]["text"] = ($indexPerson+1).' .'.($rowPerson["NAME"]??'-');
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["contents"][$indexPerson]["weight"] = "bold";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["contents"][$indexPerson]["size"] = "sm";
					$cremationData["contents"]["contents"][$indexCremation]["body"]["contents"][5]["contents"][$indexPerson]["color"] = "#000000";
				}
			}
			$indexCremation++;
		}
		$arrPostData["messages"][0] = $cremationData;
		$arrPostData["replyToken"] = $reply_token;
	}else{
		$messageResponse = "ไม่พบข้อมูลฌาปนกิจ";
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