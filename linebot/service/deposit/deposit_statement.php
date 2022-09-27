<?php
if($lineLib->checkBindAccount($user_id)){

	$arrPostData["replyToken"] = $arrMessage;
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	$arrayGroupSTM = array();
	$limit = $lineLib->getLineConstant('limit_stmdeposit');
	$arrayResult['LIMIT_DURATION'] = $limit;
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
	$rownum = 5;
	$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO < 999999";
	$account_no = preg_replace('/-/','',$deptNo);
	
	$fetchSlipTrans = $conmysql->prepare("SELECT coop_slip_no FROM gctransaction WHERE (from_account = :deptaccount_no OR destination = :deptaccount_no) and result_transaction = '-9'");
	$fetchSlipTrans->execute([':deptaccount_no' => $account_no]);
	$arrSlipTrans = array();
	$arrSlipStm = array();
	while($rowslipTrans = $fetchSlipTrans->fetch(PDO::FETCH_ASSOC)){
		$arrSlipTrans[] = $rowslipTrans["coop_slip_no"];
	}
	if(sizeof($arrSlipTrans) > 0){
		$fetchStmSeqDept = $conoracle->prepare("SELECT DPSTM_NO FROM dpdeptslip WHERE (deptslip_no IN('".implode("','",$arrSlipTrans)."') OR refer_slipno IN('".implode("','",$arrSlipTrans)."')) and deptaccount_no = :deptacc_no");
		$fetchStmSeqDept->execute([':deptacc_no' => $account_no]);
		while($rowstmseq = $fetchStmSeqDept->fetch(PDO::FETCH_ASSOC)){
			$arrSlipStm[] = $rowstmseq["DPSTM_NO"];
		}
	}
	if(sizeof($arrSlipStm) > 0){
		$getStatement = $conoracle->prepare("	SELECT * FROM (SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
												dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT,dsm.PRNCBAL
												FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
												ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
												WHERE dsm.deptaccount_no = :account_no and dsm.seq_no NOT IN('".implode("','",$arrSlipStm)."') and dsm.OPERATE_DATE 
												BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
												ORDER BY dsm.SEQ_NO DESC) WHERE rownum <= ".$rownum." ");
	}else{
		$getStatement = $conoracle->prepare("SELECT * FROM (SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
												dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT,dsm.PRNCBAL
												FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
												ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
												WHERE dsm.deptaccount_no = :account_no and dsm.OPERATE_DATE 
												BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
												ORDER BY dsm.SEQ_NO DESC) WHERE rownum <= ".$rownum." ");
	}
	$getStatement->execute([
		':account_no' => $account_no,
		':datebefore' => $date_before,
		':datenow' => $date_now
	]);
	$getMemoDP = $conmysql->prepare("SELECT memo_text,memo_icon_path,seq_no FROM gcmemodept 
										WHERE deptaccount_no = :account_no");
	$getMemoDP->execute([
		':account_no' => $account_no
	]);
	$arrMemo = array();
	while($rowMemo = $getMemoDP->fetch(PDO::FETCH_ASSOC)){
		$arrMemo[] = $rowMemo;
	}
	while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
		$arrSTM = array();
		$arrSTM["TYPE_TRAN"] = $rowStm["TYPE_TRAN"];
		$arrSTM["SIGN_FLAG"] = $rowStm["SIGN_FLAG"];
		$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
		$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
		$arrSTM["TRAN_AMOUNT"] = number_format($rowStm["TRAN_AMOUNT"],2);
		$arrSTM["PRIN_BAL"] = number_format($rowStm["PRNCBAL"],2);
		if(array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no')) === False){
			$arrSTM["MEMO_TEXT"] = null;
			$arrSTM["MEMO_ICON_PATH"] = null;
		}else{
			$arrSTM["MEMO_TEXT"] = $arrMemo[array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no'))]["memo_text"] ?? null;
			$arrSTM["MEMO_ICON_PATH"] = $arrMemo[array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no'))]["memo_icon_path"] ?? null;
		}
		$arrayGroupSTM[] = $arrSTM;
	}

	
	$headerData = $conoracle->prepare("SELECT dp.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,dp.DEPTACCOUNT_NO,dp.DEPTACCOUNT_NAME,dp.prncbal as BALANCE,
									(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE DEPTACCOUNT_NO = dp.DEPTACCOUNT_NO) as LAST_OPERATE_DATE
										FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.DEPTTYPE_CODE = dt.DEPTTYPE_CODE
										WHERE dp.member_no = :member_no and dp.deptaccount_no = :deptaccount_no and dp.deptclose_status <> 1 ORDER BY dp.DEPTACCOUNT_NO ASC");
	
	if($headerData->execute([
		':member_no' => $member_no,
		':deptaccount_no' => $account_no
	])){
		$arrHeaderData = $headerData->fetch(PDO::FETCH_ASSOC);
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
			$depositData["contents"]["body"]["contents"][0]["color"] = ($themeColor??"#000000");
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
			$depositData["contents"]["body"]["contents"][3]["contents"][1]["text"] = $lib->convertdate(date("d-m-Y"),'D m Y').' '.date('H:i').'น.'; 
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
					
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["type"] = "text";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["text"] = ($statementData["TYPE_TRAN"]??"ไม่พบชื่อประเภทเงินฝาก");
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["weight"] = "bold";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["size"] = "xxs";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][1]["margin"] = "lg";
					
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["type"] = "text";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["text"] = ($statementData["OPERATE_DATE"]??"ไม่พบวันที่ทำรายการ");
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["size"] = "xxs";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][2]["align"] = "end";

					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["type"] = "text";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["text"] = ($statementData["SIGN_FLAG"]=="1"?"+":"-").($statementData["TRAN_AMOUNT"])." บาท";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["weight"] = "bold";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["size"] = "sm";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["color"] = ($statementData["SIGN_FLAG"]=="1"?"#35B84B":"#D32F2F");
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][3]["align"] = "end";
					
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["type"] = "text";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["text"] = "คงเหลือ";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["size"] = "sm";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["align"] = "end";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["contents"][0]["type"] = "span";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["contents"][0]["text"] = "คงเหลือ : ";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["contents"][0]["color"] = "#AAAAAA";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["contents"][1]["type"] = "span";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["contents"][1]["text"] = $statementData["PRIN_BAL"]." บาท";
					$depositData["contents"]["body"]["contents"][5]["contents"][$IndexStatement]["contents"][4]["contents"][1]["weight"] = "bold";
					$IndexStatement++;
				}
			}else{
					$depositData["contents"]["body"]["contents"][5]["type"] = "text";
					$depositData["contents"]["body"]["contents"][5]["text"] = "ไม่พบรายการเคลื่อนไหว";
					$depositData["contents"]["body"]["contents"][5]["align"] = "end";
			}
			$arrPostData["messages"][0] = $depositData;
			$arrPostData["replyToken"] = $reply_token; 
		}
	}
}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>