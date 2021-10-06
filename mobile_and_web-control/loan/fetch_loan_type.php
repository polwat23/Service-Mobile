<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllLoan = array();
		$getSumAllContract = $conmssqlcoop->prepare("SELECT SUM(amount) as SUM_LOANBALANCE FROM coloanmember WHERE member_id = :member_no and status = 'A'");
		$getSumAllContract->execute([':member_no' => $member_no]);
		$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_LOANBALANCE'] = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
		$getContract = $conmssqlcoop->prepare("SELECT cd.description AS LOAN_TYPE , lm.doc_no AS LOANCONTRACT_NO, lm.amount AS APPROVE_AMT, (isnull(lm.amount,0) - isnull(lm.principal_actual,0)) as LOAN_BALANCE  ,  lm.principal_actual,	
											(SELECT max(paydate)   FROM coreceipt WHERE loan_doc_no = lm.doc_no) as LAST_OPERATE_DATE,	
											lm.startdate as STARTCONT_DATE,lm.totalseq as PERIOD, lm.amount_per_period as PERIOD_PAYMENT , lm.lastseq as LAST_PERIOD,
											lm.HOLD , lm.HOLD_PRINCIPALONLY     	
											FROM coloanmember lm LEFT JOIN cointerestrate_desc cd ON lm.Type = cd.Type	
											where lm.status = 'A' AND lm.member_id = :member_no");
		$getContract->execute([':member_no' => $member_no]);
		while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
			$arrGroupContract = array();
			$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
			$arrContract = array();
			$arrContract["CONTRACT_NO"] = $contract_no;
			$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
			$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
			$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
			$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
			$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["STARTCONT_DATE"],'D m Y');
			$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
			$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
			if($rowContract["HOLD"] == "1"){
				$arrContract["CONTRACT_STATUS"] = "พักชำระทั้งหมด";
			}else if($rowContract["HOLD_PRINCIPALONLY"] == "1"){
				$arrContract["CONTRACT_STATUS"] = "พักชำระเฉพาะเงินต้น";
			}
			
			$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
			
			if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
				($arrGroupContract['CONTRACT'])[] = $arrContract;
				$arrAllLoan[] = $arrGroupContract;
			}else{
				($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
			}
		}
		$arrayResult['DETAIL_LOAN'] = $arrAllLoan;
		$arrayResult['RESULT'] = TRUE;
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