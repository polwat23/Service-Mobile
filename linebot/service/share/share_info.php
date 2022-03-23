<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	$list_show = $lineLib->getLineConstant('limit_stmshare');
	$getSharemasterinfo = $conmssql->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT,SHAREBEGIN_AMT
													FROM shsharemaster WHERE member_no = :member_no");
	$getSharemasterinfo->execute([':member_no' => $member_no]);
	$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
	if($rowMastershare){
		$statment = array();
		$arraytmeberAccountData['BRING_FORWARD'] = number_format($rowMastershare["SHAREBEGIN_AMT"] * 10,2);
		$arraytmeberAccountData['SHARE_AMT'] = number_format($rowMastershare["SHARE_AMT"],2);
		$arraytmeberAccountData['PERIOD_SHARE_AMT'] = number_format($rowMastershare["PERIOD_SHARE_AMT"],2);
		$limit = $func->getConstant('limit_stmshare');
		$arraytmeberAccountData['LIMIT_DURATION'] = $limit;
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$getShareStatement = $conmssql->prepare("SELECT TOP ".$list_show."  stm.OPERATE_DATE,(stm.share_amount * 10) as PERIOD_SHARE_AMOUNT,
													(stm.sharestk_amt*10) as SUM_SHARE_AMT,sht.SHRITEMTYPE_DESC,stm.PERIOD,stm.REF_SLIPNO
													FROM shsharestatement stm LEFT JOIN shucfshritemtype sht ON stm.shritemtype_code = sht.shritemtype_code
													WHERE stm.member_no = :member_no and stm.shritemtype_code NOT IN ('B/F','DIV') and stm.OPERATE_DATE
													BETWEEN CONVERT(varchar, :datebefore, 23) and CONVERT(varchar, :datenow, 23) ORDER BY stm.seq_no DESC");
		$getShareStatement->execute([
			':member_no' => $member_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowStm = $getShareStatement->fetch(PDO::FETCH_ASSOC)){
			$arrayStm = array();
			$arrayStm["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrayStm["PERIOD_SHARE_AMOUNT"] = number_format($rowStm["PERIOD_SHARE_AMOUNT"],2);
			$arrayStm["SUM_SHARE_AMT"] = number_format($rowStm["SUM_SHARE_AMT"],2);
			$arrayStm["SHARETYPE_DESC"] = $rowStm["SHRITEMTYPE_DESC"];
			$arrayStm["PERIOD"] = $rowStm["PERIOD"];
			$arrayStm["SLIP_NO"] = $rowStm["REF_SLIPNO"];
			$statment[] = $arrayStm;
		}
		
		$shareDataInfo = array();
		$shareDataInfo["type"] = "flex";
		$shareDataInfo["altText"] = "ข้อมูลหุ้น";
		$shareDataInfo["contents"]["type"] = "bubble";
		$shareDataInfo["contents"]["direction"] = "ltr";
		$shareDataInfo["contents"]["body"]["type"] = "box";
		$shareDataInfo["contents"]["body"]["layout"] = "vertical";
		$shareDataInfo["contents"]["body"]["contents"][0]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][0]["text"] = "หุ้น";
		$shareDataInfo["contents"]["body"]["contents"][0]["weight"] = "bold";
		$shareDataInfo["contents"]["body"]["contents"][0]["size"] = "lg";
		$shareDataInfo["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
		$shareDataInfo["contents"]["body"]["contents"][1]["type"] = "image";
		$shareDataInfo["contents"]["body"]["contents"][1]["url"] = "https://cdn.thaicoop.co/icon/profits.png";
		$shareDataInfo["contents"]["body"]["contents"][1]["size"] = "xs";
		$shareDataInfo["contents"]["body"]["contents"][1]["position"] = "absolute";
		$shareDataInfo["contents"]["body"]["contents"][1]["offsetTop"] = "60px";
		$shareDataInfo["contents"]["body"]["contents"][1]["offsetStart"] = "20px";
		$shareDataInfo["contents"]["body"]["contents"][2]["type"] = "box";
		$shareDataInfo["contents"]["body"]["contents"][2]["layout"] = "vertical";
		$shareDataInfo["contents"]["body"]["contents"][2]["spacing"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["margin"] = "lg";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][0]["text"] = "หุ้นสะสม";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][0]["align"] = "end";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][1]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][1]["text"] = ($arraytmeberAccountData["SHARE_AMT"]??'-')." บาท";   
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][1]["weight"] = "bold";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][1]["size"] = "xl";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][1]["color"] = "#0EA7CA";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][1]["align"] = "end";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["type"] = "box";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["layout"] = "baseline";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["spacing"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][0]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][0]["text"] = "ยอดยกมาต้นปี";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][0]["size"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][0]["color"] = "#AAAAAA";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][1]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][1]["text"] = ($arraytmeberAccountData["BRING_FORWARD"]??'-')." บาท";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][1]["weight"] = "bold";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][1]["size"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][1]["align"] = "end";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][2]["contents"][1]["wrap"] = true;
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["type"] = "box";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["layout"] = "baseline";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["spacing"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][0]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][0]["text"] = "หุ้นรายเดือน";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][0]["size"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][0]["color"] = "#AAAAAA";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][1]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][1]["text"] = ($arraytmeberAccountData["PERIOD_SHARE_AMT"]??'-')." บาท" ;
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][1]["weight"] = "bold";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][1]["size"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][2]["contents"][3]["contents"][1]["align"] = "end";
		$shareDataInfo["contents"]["body"]["contents"][3]["type"] = "text";
		$shareDataInfo["contents"]["body"]["contents"][3]["text"] = "รายการเคลื่อนไหว";
		$shareDataInfo["contents"]["body"]["contents"][3]["weight"] = "bold";
		$shareDataInfo["contents"]["body"]["contents"][3]["size"] = "sm";
		$shareDataInfo["contents"]["body"]["contents"][3]["color"] = "#1885C3";
		$shareDataInfo["contents"]["body"]["contents"][3]["margin"] = "md";
		$shareDataInfo["contents"]["body"]["contents"][4]["type"] = "box";
		$shareDataInfo["contents"]["body"]["contents"][4]["layout"] = "vertical";

		if(sizeof($statment) > 0){
			$indexStatment = 0;
			foreach($statment as $statmentData){
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["type"] = "box";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["layout"] = "vertical";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["margin"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][0]["type"] = "separator";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][0]["margin"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][0]["color"] = "#D3C9C9FF";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][1]["type"] = "text";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][1]["text"] = ($statmentData["SHARETYPE_DESC"]??"-");
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][1]["weight"] = "bold";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][1]["size"] = "xxs";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][1]["margin"] = "md";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][2]["type"] = "text";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][2]["text"] = ($statmentData["OPERATE_DATE"]??"-");
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][2]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][2]["align"] = "end";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][2]["wrap"] = true;
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][3]["type"] = "text";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][3]["text"] = ($statmentData["PERIOD_SHARE_AMOUNT"]??"-")." บาท" ;
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][3]["weight"] = "bold";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][3]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][3]["color"] = "#229954";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][3]["align"] = "end";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["type"] = "text";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["text"] = "งวด ";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["align"] = "end";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["contents"][0]["type"] = "span";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["contents"][0]["text"] = "งวด :";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["contents"][1]["type"] = "span";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][4]["contents"][1]["text"] = ($statmentData["PERIOD"]??"-");
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["type"] = "text";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["text"] = "หุ้นสะสม :";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["align"] = "end";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][0]["type"] = "span";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][0]["text"] = "หุ้นสะสม : ";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][0]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][0]["color"] = "#AAAAAA";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][1]["type"] = "span";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][1]["text"] = ($statmentData["SUM_SHARE_AMT"]??"-")." บาท"; 
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][1]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][5]["contents"][1]["weight"] = "bold";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["type"] = "text";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["text"] = "เลขที่ใบเสร็จ";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["align"] = "end";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][0]["type"] = "span";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][0]["text"] = "เลขที่ใบเสร็จ :";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][0]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][0]["color"] = "#AAAAAA";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][1]["type"] = "span";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][1]["text"] = ($statmentData["SLIP_NO"]??"-"); 
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][1]["size"] = "sm";
				$shareDataInfo["contents"]["body"]["contents"][4]["contents"][$indexStatment]["contents"][6]["contents"][1]["weight"] = "bold";
				$indexStatment++;
			}
			
		}else{
			$shareDataInfo["contents"]["body"]["contents"][4]["contents"][0]["type"] = "text";
			$shareDataInfo["contents"]["body"]["contents"][4]["contents"][0]["text"] = "ไม่พบรายการเคลื่อนไหว";
		}
		$arrPostData["messages"][0] = $shareDataInfo;
		$arrPostData["replyToken"] = $reply_token;
	//	$arrPostData["replyToken"] = $reply_token;
	}
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูลหุ้น";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>