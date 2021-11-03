<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchLoanRepay = $conoracle->prepare("SELECT LOANCONTRACT_NO,PRINCIPAL_BALANCE,WITHDRAWABLE_AMT
												FROM lncontmaster
												WHERE loancontract_no = :contract_no and contract_status > 0 and contract_status <> 8");
		$fetchLoanRepay->execute([':contract_no' => $contract_no]);
		$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
		if($dataComing["amt_transfer"] > $rowLoan["WITHDRAWABLE_AMT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0093';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}else{
			$interest = $cal_loan->calculateInterestArr($contract_no,$dataComing["amt_transfer"]);
			$arrOther = array();
			if($interest > 0){
				$arrOther["LABEL"] = 'ดอกเบี้ย';
				$arrOther["VALUE"] = number_format($interest,2)." บาท";
				$arrayResult["OTHER_INFO"][] = $arrOther;
			}
			$arrOther["LABEL"] = 'หนี้คงเหลือหลังทำรายการ';
			$arrOther["VALUE"] = number_format($rowLoan["PRINCIPAL_BALANCE"] + $dataComing["amt_transfer"],2)." บาท";
			$arrayResult["OTHER_INFO"][] = $arrOther;
			$arrayResult['RESULT'] = TRUE;
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