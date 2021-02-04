<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','loancontract_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$fetchLoanRepay = $conoracle->prepare("SELECT lnt.loantype_desc,lnm.loancontract_no,lnm.principal_balance,lnm.period_payamt,lnm.last_periodpay,lnm.LOANTYPE_CODE,
												lnm.LASTCALINT_DATE,lnm.LOANPAYMENT_TYPE,
												(CASE WHEN lnm.lastprocess_date <= lnm.LASTCALINT_DATE OR lnm.lastprocess_date IS NULL THEN '1' ELSE '0' END) as CHECK_KEEPING
												FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
												WHERE lnm.loancontract_no = :loancontract_no and lnm.contract_status > 0 and lnm.contract_status <> 8");
		$fetchLoanRepay->execute([':loancontract_no' => $dataComing["loancontract_no"]]);
		$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
		$arrLoan = array();
		$interest = 0;
		if($rowLoan["CHECK_KEEPING"] == '1'){
			$interest = $calloan->calculateInterest($dataComing["loancontract_no"]);
			if($dataComing["amt_transfer"] < $interest){
				$interest = $dataComing["amt_transfer"];
			}else{
				$interest = $interest;
				$prinPay = $dataComing["amt_transfer"] - $interest;
			}
			if($prinPay < 0){
				$prinPay = 0;
			}
			$arrayResult["PAYMENT_INT"] = $interest;
			$arrayResult["PAYMENT_PRIN"] = $prinPay;
		}else{
			$arrayResult["PAYMENT_PRIN"] = $dataComing["amt_transfer"];
		}
		if($dataComing["amt_transfer"] > $rowLoan["PRINCIPAL_BALANCE"] + $interest){
			$arrayResult['RESPONSE_CODE'] = "WS0098";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
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