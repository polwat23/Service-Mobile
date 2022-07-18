<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrLoanGrp = array();
		
		$fetchLoanRepay = $conoracle->prepare("SELECT lnt.loantype_desc,lnm.loancontract_no,lnm.principal_balance,lnm.period_payamt,lnm.last_periodpay,lnm.LOANTYPE_CODE,
												lnm.LASTCALINT_DATE,lnm.LOANPAYMENT_TYPE,lnm.INTEREST_RETURN
												FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
												WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8");
		$fetchLoanRepay->execute([':member_no' => $member_no]);
		while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
			$interest = 0;
			$arrLoan = array();
			$interest = $cal_loan->calculateInterest($rowLoan["LOANCONTRACT_NO"]);
			if($interest > 0){
				$arrLoan["INT_BALANCE"] = $interest;
			}
			if(file_exists(__DIR__.'/../../resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png')){
				$arrLoan["LOAN_TYPE_IMG"] = $config["URL_SERVICE"].'resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png?v='.date('Ym');
			}else{
				$arrLoan["LOAN_TYPE_IMG"] = null;
			}
			$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
			$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
			$arrLoan["BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"],2);
			$arrLoan["PERIOD_ALL"] = number_format($rowLoan["PERIOD_PAYAMT"],0);
			$arrLoan["PERIOD_BALANCE"] = number_format($rowLoan["LAST_PERIODPAY"],0);
			$arrLoanGrp[] = $arrLoan;
		}
		
		if(sizeof($arrLoanGrp) > 0){
			$arrayResult['LOAN'] = $arrLoanGrp;
			$arrayResult['IS_SAVE_SOURCE'] = FALSE;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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