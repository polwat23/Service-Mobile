<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrGroupAccount = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmloan');
		$arrayResult['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$fetchLastStmAcc = $conmssqlcoop->prepare("select TOP 1 lnm.doc_no AS LOANCONTRACT_NO ,  lt.description AS LOAN_TYPE,rec.principalbf AS LOAN_BALANCE,	
														lnm.amount AS APPROVE_AMT,lnm.startdate AS STARTCONT_DATE,lnm.amount_per_period as PERIOD_PAYMENT,
														lnm.totalseq as PERIOD, rec.paydate AS OPERATE_DATE , lnm.lastseq as LAST_PERIOD
														from coloanmember lnm LEFT JOIN  coreceipt rec ON lnm.doc_no  = rec.loan_doc_no
														LEFT JOIN cointerestrate_desc lt ON lnm.type = lt.type	
														where lnm.member_id = :member_no  AND  lnm.status = 'A' ORDER BY rec.paydate DESC");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowLoanLastSTM = $fetchLastStmAcc->fetch(PDO::FETCH_ASSOC);
		$contract_no = preg_replace('/\//','',$rowLoanLastSTM["LOANCONTRACT_NO"]);
		$arrContract = array();
		$arrContract["CONTRACT_NO"] = $contract_no;
		$arrContract["LOAN_BALANCE"] = number_format($rowLoanLastSTM["LOAN_BALANCE"],2);
		$arrContract["APPROVE_AMT"] = number_format($rowLoanLastSTM["APPROVE_AMT"],2);
		$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowLoanLastSTM["LAST_OPERATE_DATE"],'y-n-d');
		$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowLoanLastSTM["LAST_OPERATE_DATE"],'D m Y');
		$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowLoanLastSTM["STARTCONT_DATE"],'D m Y');
		$arrContract["PERIOD_PAYMENT"] = number_format($rowLoanLastSTM["PERIOD_PAYMENT"],2);
		$arrContract["PERIOD"] = $rowLoanLastSTM["LAST_PERIOD"].' / '.$rowLoanLastSTM["PERIOD"];
		$arrContract["DATA_TIME"] = date('H:i');
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_loan');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and rec.LOAN_SEQ > ".$dataComing["old_seq_no"] : "and rec.LOAN_SEQ > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and rec.LOAN_SEQ < ".$dataComing["old_seq_no"] : "and rec.LOAN_SEQ < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and rec.LOAN_SEQ < ".$dataComing["old_seq_no"] : "and rec.LOAN_SEQ < 999999";
		}
		$getStatement = $conmssqlcoop->prepare("select TOP ".$rownum."  rct.description  AS TYPE_DESC,rec.paydate AS OPERATE_DATE,	
													rec.principal AS PRN_PAYMENT,rec.loan_seq AS SEQ_NO,rec.interest AS INT_PAYMENT,rec.principalbf AS LOAN_BALANCE	
													from coreceipt rec LEFT JOIN coreceipttype rct ON rec.type = rct.type	
													where rec.loan_doc_no = ? and rec.paydate BETWEEN CONVERT(varchar, ? , 23) and CONVERT(varchar, ? , 23) ".$old_seq_no."	
													ORDER BY rec.loan_seq DESC");
		$getStatement->execute([$contract_no, $date_before,$date_now]);
		while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
			$arrSTM = array();
			$arrSTM["TYPE_DESC"] = $rowStm["TYPE_DESC"];
			$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["PRN_PAYMENT"] = number_format($rowStm["PRN_PAYMENT"],2);
			$arrSTM["INT_PAYMENT"] = number_format($rowStm["INT_PAYMENT"],2);
			$arrSTM["SUM_PAYMENT"] = number_format($rowStm["INT_PAYMENT"] + $rowStm["PRN_PAYMENT"],2);
			$arrSTM["LOAN_BALANCE"] = number_format($rowStm["LOAN_BALANCE"],2);
			$arrayGroupSTM[] = $arrSTM;
		}
		$arrayResult["HEADER"] = $arrContract;
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>