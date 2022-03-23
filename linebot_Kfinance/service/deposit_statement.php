<?php

	//cal api server
	$url = "http://103.233.193.52/line_bot/mobile_and_web-control/check_member/check_member";
	$data["member_info"]= $message;//"3900900541209/0818173439";
	$memberInfo = $lineLib->sendApiToServer($data,$url);
	$groupData = json_decode($memberInfo["DATA"],true);


	if(sizeof($arrHeaderData)>1){
		$depositData = array();
		$depositData["type"] = "flex";
		$depositData["altText"] = "ข้อมูลบัญชี". $lib->formataccount($arrHeaderData["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
		$depositData["contents"]["type"] = "bubble";
		$depositData["contents"]["body"]["type"] = "box";
		$depositData["contents"]["body"]["layout"] = "vertical";
		$depositData["contents"]["body"]["contents"][0]["type"] = "text";
		$depositData["contents"]["body"]["contents"][0]["text"] = ($arrHeaderData["DEPTTYPE_DESC"]??'ไม่พบชื่อบัญชี');   
		$depositData["contents"]["body"]["contents"][0]["weight"] = "bold";
		$depositData["contents"]["body"]["contents"][0]["size"] = "lg";
		$depositData["contents"]["body"]["contents"][0]["color"] = "#E3519D";
		$depositData["contents"]["body"]["contents"][0]["align"] = "center";
		$depositData["contents"]["body"]["contents"][1]["type"] = "box";
		$depositData["contents"]["body"]["contents"][1]["layout"] = "baseline";
		$depositData["contents"]["body"]["contents"][1]["contents"][0]["type"] = "text";
		$depositData["contents"]["body"]["contents"][1]["contents"][0]["text"] = "เลขที่บัญชี :";
		$depositData["contents"]["body"]["contents"][1]["contents"][0]["size"] = "xxs";
		$depositData["contents"]["body"]["contents"][1]["contents"][0]["color"] = "#AAAAAA";
		$depositData["contents"]["body"]["contents"][1]["contents"][1]["type"] = "text";
		$depositData["contents"]["body"]["contents"][1]["contents"][1]["text"] = ($lib->formataccount($arrHeaderData["DEPTACCOUNT_NO"],$func->getConstant('dep_format'))??'ไม่พบเลขที่บัญชี');
		$depositData["contents"]["body"]["contents"][1]["contents"][1]["weight"] = "bold";
		$depositData["contents"]["body"]["contents"][1]["contents"][1]["size"] = "xxs";
		$depositData["contents"]["body"]["contents"][1]["contents"][1]["align"] = "end";
		$depositData["contents"]["body"]["contents"][2]["type"] = "box";
		$depositData["contents"]["body"]["contents"][2]["layout"] = "baseline";
		$depositData["contents"]["body"]["contents"][2]["spacing"] = "none";
		$depositData["contents"]["body"]["contents"][2]["margin"] = "md";
		$depositData["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
		$depositData["contents"]["body"]["contents"][2]["contents"][0]["text"] = "ชื่อบัญชี :";
		$depositData["contents"]["body"]["contents"][2]["contents"][0]["size"] = "xxs";
		$depositData["contents"]["body"]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
		$depositData["contents"]["body"]["contents"][2]["contents"][1]["type"] = "text";
		$depositData["contents"]["body"]["contents"][2]["contents"][1]["text"] = ($arrHeaderData["DEPTACCOUNT_NAME"]??"ไม่พบชื่อบัญชี") ;
		$depositData["contents"]["body"]["contents"][2]["contents"][1]["size"] = "xxs";
		$depositData["contents"]["body"]["contents"][2]["contents"][1]["wrap"] = true;
		$depositData["contents"]["body"]["contents"][2]["contents"][1]["align"] = "end";
		$depositData["contents"]["body"]["contents"][3]["type"] = "box";
		$depositData["contents"]["body"]["contents"][3]["layout"] = "baseline";
		$depositData["contents"]["body"]["contents"][3]["contents"][0]["type"] = "text";
		$depositData["contents"]["body"]["contents"][3]["contents"][0]["text"] = "คงเหลือ";
		$depositData["contents"]["body"]["contents"][3]["contents"][0]["size"] = "sm";
		$depositData["contents"]["body"]["contents"][3]["contents"][1]["type"] = "text";
		$depositData["contents"]["body"]["contents"][3]["contents"][1]["text"] = $lib->convertdate(date("d-m-Y"),'D m Y')." ".date('H:i'); 
		$depositData["contents"]["body"]["contents"][3]["contents"][1]["size"] = "xxs";
		$depositData["contents"]["body"]["contents"][3]["contents"][1]["align"] = "end";
		$depositData["contents"]["body"]["contents"][4]["type"] = "text";
		$depositData["contents"]["body"]["contents"][4]["text"] = (number_format($arrHeaderData["BALANCE"],2) ??"0.00")." บาท"; 
		$depositData["contents"]["body"]["contents"][4]["weight"] = "bold";
		$depositData["contents"]["body"]["contents"][4]["size"] = "xl";
		$depositData["contents"]["body"]["contents"][4]["color"] = "#0EA7CA";
		$depositData["contents"]["body"]["contents"][4]["align"] = "start";
		$depositData["contents"]["body"]["contents"][4]["offsetStart"] = "40px";
		$IndexStatement = 0;
		if(sizeof($arrayGroupSTM) > 0){ 
			foreach($arrayGroupSTM as $statementData){
				$depositData["contents"]["body"]["contents"][5]["type"] = "box";
				$depositData["contents"]["body"]["contents"][5]["layout"] = "vertical";
				$depositData["contents"]["body"]["contents"][5]["margin"] = "md";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["type"] = "box";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["layout"] = "vertical";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][0]["type"] = "separator";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["type"] = "box";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["layout"] = "baseline";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["margin"] = "lg";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][0]["type"] = "text";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][0]["text"] = ($statementData["TYPE_TRAN"]??"ไม่พบชื่อประเภทเงินฝาก");
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][0]["weight"] = "bold";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][0]["size"] = "xxs";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][1]["type"] = "text";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][1]["text"] = ($statementData["OPERATE_DATE"]??"ไม่พบวันที่ทำรายการ");
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][1]["size"] = "xxs";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["contents"][1]["align"] = "end";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["type"] = "text";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["text"] = ($statementData["SIGN_FLAG"]=="1"?"+":"-").($statementData["TRAN_AMOUNT"])." บาท";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["weight"] = "bold";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["size"] = "sm";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["color"] = ($statementData["SIGN_FLAG"]=="1"?"#35B84B":"#D32F2F");
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["align"] = "end";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["type"] = "text";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["text"] = "คงเหลือ";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["size"] = "sm";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["align"] = "end";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["contents"][0]["type"] = "span";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["contents"][0]["text"] = "คงเหลือ : ";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["contents"][1]["type"] = "span";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["contents"][1]["text"] = $statementData["PRIN_BAL"]." บาท";
				$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["contents"][1]["weight"] = "bold";
				$IndexStatement++;
			}
		}else{
				$depositData["contents"]["body"]["contents"][5]["type"] = "text";
				$depositData["contents"]["body"]["contents"][5]["text"] = "ไม่พบรายการเคลื่อนไหว";
				$depositData["contents"]["body"]["contents"][5]["align"] = "end";
		}
		//$arrPostData["messages"][0] = $depositData;
		//$arrPostData["replyToken"] = $reply_token; 
		
		
	}
?>