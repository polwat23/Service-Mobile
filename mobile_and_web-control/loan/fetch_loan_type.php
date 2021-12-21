<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllLoan = array();
		$getSumAllContract = $conoracle->prepare("SELECT SUM(LCONT_AMOUNT_SAL) as SUM_LOANBALANCE FROM LOAN_M_CONTACT WHERE account_id = :member_no
													and LCONT_STATUS_CONT IN('H','A')");
		$getSumAllContract->execute([':member_no' => $member_no]);
		$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_LOANBALANCE'] = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
		$getContract = $conoracle->prepare("SELECT lt.L_TYPE_NAME AS LOAN_TYPE,ln.LCONT_ID as loancontract_no,
											ln.LCONT_AMOUNT_SAL as LOAN_BALANCE,
											ln.LCONT_APPROVE_SAL as APPROVE_AMT,ln.LCONT_DATE as startcont_date,
											ln.LCONT_SAL as period_payment,ln.LCONT_MAX_INSTALL as PERIOD,
											ln.LCONT_MAX_INSTALL - ln.LCONT_NUM_INST as LAST_PERIOD,
											ln.LCONT_PAY_LAST_DATE as LAST_OPERATE_DATE
											FROM LOAN_M_CONTACT ln LEFT JOIN LOAN_M_TYPE_NAME lt ON ln.L_TYPE_CODE = lt.L_TYPE_CODE 
											WHERE ln.account_id = :member_no and ln.LCONT_STATUS_CONT IN('H','A')");
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