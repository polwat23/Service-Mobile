<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	$arrayGroupLoan = array();
	$getUcollwho = $conmssql->prepare("SELECT
										RTRIM(LCC.LOANCONTRACT_NO) AS LOANCONTRACT_NO,
										LNTYPE.loantype_desc as TYPE_DESC,
										PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
										LCM.MEMBER_NO AS MEMBER_NO,
										ISNULL(LCM.LOANAPPROVE_AMT,0) as LOANAPPROVE_AMT,
										ISNULL(LCM.PRINCIPAL_BALANCE,0) as LOAN_BALANCE
										FROM
										LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
										LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
										LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
										LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
										WHERE
										LCM.CONTRACT_STATUS > 0 and LCM.CONTRACT_STATUS <> 8
										AND LCC.LOANCOLLTYPE_CODE = '01'
										AND LCC.REF_COLLNO = :member_no
										AND LCC.COLL_STATUS = '1'"
									);
	$getUcollwho->execute([':member_no' => $member_no]);
	while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
		$arrayColl = array();
		$arrayColl["CONTRACT_NO"] = $rowUcollwho["LOANCONTRACT_NO"];
		$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
		$arrayColl["MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
		$arrayAvarTar = $func->getPathpic($rowUcollwho["MEMBER_NO"]);
		$arrayColl["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
		$arrayColl["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
		$arrayColl["APPROVE_AMT"] = number_format($rowUcollwho["LOANAPPROVE_AMT"],2);
		$arrayColl["LOAN_BALANCE"] = number_format($rowUcollwho["LOAN_BALANCE"],2);
		$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
		$arrayGroupLoan[] = $arrayColl;
	}
	
	$ListDatas  = array_chunk($arrayGroupLoan, 12);
	$indexListDatas = 0;
	$groupTest = array();
	foreach($ListDatas as $rowListData){
		foreach($rowListData as $listData){
			$datas = array();
			$datas["type"] = "flex";
			$datas["altText"] = "ใคค้ำคุณ";
			$datas["contents"]["type"] = "carousel";
			$indexContents = 0;
			if(sizeof($rowListData)>0){
				foreach($rowListData as $loan){
					$datas["contents"]["contents"][$indexContents]["type"] = "bubble";
					$datas["contents"]["contents"][$indexContents]["direction"] = "ltr";
					$datas["contents"]["contents"][$indexContents]["body"]["type"] = "box";
					$datas["contents"]["contents"][$indexContents]["body"]["layout"] = "vertical";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["type"] = "box";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["layout"] = "vertical";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["type"] = "box";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["layout"] = "horizontal";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["margin"] = "lg";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][0]["text"] = "เลขที่สัญญา";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][0]["color"] = "#0938A4";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][0]["align"] = "start";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][1]["text"] = ($loan["CONTRACT_NO"]??"*-");
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][1]["weight"] = "bold";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][1]["size"] = "xs";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][0]["contents"][1]["align"] = "end";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["text"] = "ประเภทเงินกู้";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["size"] = "xs";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][1]["color"] = "#0938A4";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["text"] = ($loan["TYPE_DESC"]??"*-");
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["size"] = "xs";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][2]["align"] = "end";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["type"] = "box";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["layout"] = "horizontal";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["text"] = "วงเงินกู้";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["color"] = "#0938A4";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][0]["align"] = "start";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["text"] = ($loan["APPROVE_AMT"]??"*-")." บาท";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["weight"] = "bold";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["size"] = "sm";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["color"] = "#35B84B";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][3]["contents"][1]["align"] = "end";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["type"] = "box";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["layout"] = "horizontal";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["text"] = "เงินกู้คงเหลือ";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["size"] = "xs";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["color"] = "#0938A4";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][0]["align"] = "start";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["text"] = ($loan["LOAN_BALANCE"]??"*-")." บาท";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["weight"] = "bold";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["size"] = "sm";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["color"] = "#EA5F0FFF";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][4]["contents"][1]["align"] = "end";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["text"] = "ผู้กู้";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["weight"] = "bold";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["size"] = "md";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][5]["margin"] = "md";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["type"] = "box";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["layout"] = "vertical";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["margin"] = "md";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["paddingTop"] = "5px";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["paddingBottom"] = "5px";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["paddingStart"] = "20px";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["borderWidth"] = "1px";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["borderColor"] = "#E3519D";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["cornerRadius"] = "10px";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["text"] = ($loan["FULL_NAME"]??"*-");
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["size"] = "sm";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][0]["color"] = "#000000";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][1]["type"] = "text";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][1]["text"] = "เลขที่สมาชิก  :  ".($loan["MEMBER_NO"]??'-');
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][1]["size"] = "sm";
					$datas["contents"]["contents"][$indexContents]["body"]["contents"][0]["contents"][6]["contents"][1]["color"] = "#0938A4";
					$indexContents++;
				}
			}else{
				$datas["contents"]["contents"][0]["type"] = "bubble";
				$datas["contents"]["contents"][0]["direction"] = "ltr";
				$datas["contents"]["contents"][0]["body"]["type"] = "box";
				$datas["contents"]["contents"][0]["body"]["layout"] = "vertical";
				$datas["contents"]["contents"][0]["body"]["contents"][0]["type"] = "text";
				$datas["contents"]["contents"][0]["body"]["contents"][0]["text"] = "ไม่พบเข้อมูล";
				$datas["contents"]["contents"][0]["body"]["contents"][0]["margin"] = "md";
			}
		}
		$arrPostData["messages"][$indexListDatas] = $datas;
		$arrPostData["replyToken"] = $reply_token;
		$indexListDatas++;
	}
	
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>