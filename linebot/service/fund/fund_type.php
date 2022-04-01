<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrayGroupFund = array();
	$getFundInfo = $conmssql->prepare("SELECT mp.PRENAME_DESC,wcm.DEPTACCOUNT_NAME,wcm.DEPTACCOUNT_SNAME,wmt.WCMEMBER_DESC,wcm.DEPTACCOUNT_NO,wcm.DEPTOPEN_DATE,wcm.WFTYPE_CODE
										FROM WCDEPTMASTER wcm LEFT JOIN mbucfprename mp ON wcm.prename_code = mp.prename_code
										LEFT JOIN WCMEMBERTYPE wmt ON wcm.wftype_code = wmt.wftype_code and wcm.wc_id = wmt.wc_id
										WHERE wcm.member_no = :member_no and wcm.deptclose_status = '0'");
	$getFundInfo->execute([':member_no' => $member_no]);
	while($rowFund = $getFundInfo->fetch(PDO::FETCH_ASSOC)){
		$arrayFund = array();
		$arrayFund["NAME"] = $rowFund["PRENAME_DESC"].$rowFund["DEPTACCOUNT_NAME"].' '.$rowFund["DEPTACCOUNT_SNAME"];
		$arrayFund["FUND_TYPE"] = $rowFund["WCMEMBER_DESC"];
		$arrayFund["FUND_ACCOUNT"] = $rowFund["DEPTACCOUNT_NO"];
		$arrayFund["FUND_OPEN"] = $lib->convertdate($rowFund["DEPTOPEN_DATE"],'d m Y');
		if($rowFund["WFTYPE_CODE"] == '01'){
			$arrayFund["FUND_PROTECT"] = '500,000';
		}else if($rowFund["WFTYPE_CODE"] == '03'){
			$arrayFund["FUND_PROTECT"] = '100,000';
		}else if($rowFund["WFTYPE_CODE"] == '02'){
			$arrayFund["FUND_PROTECT"] = '100,000';
		}
		$getReceive = $conmssql->prepare("SELECT wp.TRANSFEREE_NAME,wp.TRANSFEREE_RELATION , mg.GAIN_CONCERN
										  FROM WCCODEPOSIT wp
										  LEFT JOIN  mbucfgainconcern mg ON  wp.TRANSFEREE_RELATION = mg.concern_code
                                          WHERE wp.deptaccount_no = :deptaccount_no ORDER BY wp.seq_no");
		$getReceive->execute([':deptaccount_no' => $rowFund["DEPTACCOUNT_NO"]]);
		$arrayFund["RECEIVE"] = array();
		while($rowReceive = $getReceive->fetch(PDO::FETCH_ASSOC)){
			$arrReceive = array();
			$arrReceive["RECEIVE_NAME"] = $rowReceive["TRANSFEREE_NAME"];
			$arrReceive["RECEIVE_RELATION"] = ($rowReceive["GAIN_CONCERN"] == ''?($rowReceive["TRANSFEREE_RELATION"]??'-'):$rowReceive["GAIN_CONCERN"]==''?'-':$rowReceive["GAIN_CONCERN"]);
			$arrayFund["RECEIVE"][] = $arrReceive;
		}
		$arrayGroupFund[] = $arrayFund;
	}
	if(sizeof($arrayGroupFund) > 0){
		$fundDatas = array();
		$fundDatas["type"] = "flex";
		$fundDatas["altText"] = "กองทุนสวัสดิการ";
		$fundDatas["contents"]["type"] = "carousel";
		$indexFund = 0;
		foreach($arrayGroupFund as $rowFund){
			$fundDatas["contents"]["contents"][$indexFund]["type"] = "bubble";
			$fundDatas["contents"]["contents"][$indexFund]["direction"] = "ltr";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["type"] = "box";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["layout"] = "vertical";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][0]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][0]["text"] = ($rowFund["FUND_TYPE"]??'-');
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][0]["weight"] = "bold";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][0]["size"] = "md";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][0]["align"] = "center";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][1]["type"] = "separator";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][1]["margin"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["type"] = "box";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["layout"] = "vertical";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["margin"] = "md";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["action"]["type"] = "message";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["action"]["label"] = "label";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["action"]["text"] = "ใบเสร็จกองทุนสวัสดิการ:".($rowFund["FUND_ACCOUNT"]??'-');
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["offsetStart"] = "110px";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["paddingAll"] = "5px";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["width"] = "150px";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["backgroundColor"] = "#EA5F0FFF";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["cornerRadius"] = "10px";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["contents"][0]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["contents"][0]["text"] = "ดูใบเสร็จรับเงิน";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["contents"][0]["size"] = "md";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["contents"][0]["color"] = "#FFFFFF";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][2]["contents"][0]["align"] = "center";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][3]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][3]["text"] = "ชื่อกองทุน";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][3]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][3]["color"] = "#AAAAAA";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][3]["margin"] = "md";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][4]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][4]["text"] = ($rowFund["NAME"]??'-');
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][4]["weight"] = "bold";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][4]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][4]["color"] = "#000000";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][4]["align"] = "end";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["type"] = "box";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["layout"] = "horizontal";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][0]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][0]["text"] = "เลขที่กองทุน";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][0]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][1]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][1]["text"] = ($rowFund["FUND_ACCOUNT"]??'-'); 
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][1]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][1]["color"] = "#000000";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][5]["contents"][1]["align"] = "end";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["type"] = "box";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["layout"] = "horizontal";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][0]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][0]["text"] = "วันที่เป็นสมาชิก";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][0]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][0]["color"] = "#AAAAAA";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][1]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][1]["text"] = ($rowFund["FUND_OPEN"]??'-');
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][1]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][1]["color"] = "#000000";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][6]["contents"][1]["align"] = "end";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["type"] = "box";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["layout"] = "horizontal";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][0]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][0]["text"] = "วงเงินคุ้มครอง";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][0]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][0]["color"] = "#AAAAAA";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][1]["type"] = "text";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][1]["text"] = ($rowFund["FUND_PROTECT"]??'-')." บาท";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][1]["weight"] = "bold";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][1]["size"] = "sm";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][1]["color"] = "#35B84B";
			$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][7]["contents"][1]["align"] = "end";
			$arrReceive  = $rowFund["RECEIVE"]??[];
			if(sizeof($arrReceive)>0){
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][8]["type"] = "text";
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][8]["text"] = "ผู้รับผลประโยชน์";
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][8]["weight"] = "bold";
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][8]["size"] = "sm";
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][8]["color"] = "#000000";
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][8]["margin"] = "xs";
				$indexReceive = 0;
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["type"] = "box";
				$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["layout"] = "vertical";
				foreach($rowFund["RECEIVE"] as $rowReceive){
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["type"] = "box";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["layout"] = "vertical";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["margin"] = "sm";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["paddingStart"] = "10px";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["borderWidth"] = "1px";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["borderColor"] = ($themeColor??"#000000");
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["cornerRadius"] = "10px";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][0]["type"] = "text";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][0]["text"] = ($rowReceive["RECEIVE_NAME"]??'-');
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][0]["size"] = "sm";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["type"] = "text";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["text"] = "text";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["size"] = "sm";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["contents"][0]["type"] = "span";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["contents"][0]["text"] = "ความสัมพันธ์ : ";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["contents"][0]["color"] = "#AAAAAA";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["contents"][1]["type"] = "span";
					$fundDatas["contents"]["contents"][$indexFund]["body"]["contents"][9]["contents"][$indexReceive]["contents"][1]["contents"][1]["text"] = ($rowReceive["RECEIVE_RELATION"]??'-');
				
					$indexReceive++;
				}
			}
			$indexFund++;
		}
		$arrPostData["messages"][0] = $fundDatas;
		$arrPostData["replyToken"] = $reply_token; 
		//$arrPostData["replyToken"] = $arrayGroupFund; 
		
	}else{
		$messageResponse = "ไม่พบข้อมูลกองทุนสวัสดิการ";
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