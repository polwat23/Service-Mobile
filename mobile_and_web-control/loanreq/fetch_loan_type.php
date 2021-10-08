<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpLoan = array();
		$getLoantype = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_loanrequest = '1'");
		$getLoantype->execute();
		while($rowLoantype = $getLoantype->fetch(PDO::FETCH_ASSOC)){
			$arrayLoan = array();
			$arrayLoan["LOANTYPE_CODE"] = $rowLoantype["loantype_code"];
			$getLoanTypeData = $conoracle->prepare("SELECT ln.LOANTYPE_CODE,lnt.INTEREST_RATE*100 as INT_RATE,ln.LOANTYPE_DESC
																		FROM lnloantype ln LEFT JOIN lncfloanintratedet lnt ON ln.inttabrate_code = lnt.loanintrate_code
																		and sysdate BETWEEN lnt.effective_date and lnt.expire_date
																		WHERE ln.loantype_code = :loantype_code");
			$getLoanTypeData->execute([
				':loantype_code' => $rowLoantype["loantype_code"]
			]);
			$rowLoanData = $getLoanTypeData->fetch(PDO::FETCH_ASSOC);
			if(isset($rowLoanData["LOANTYPE_CODE"])){
				$arrayGrpLoan[] = $rowLoanData;
			}
		}
		$arrayResult['LOAN_TYPE'] = $arrayGrpLoan;
		$arrayResult['SKIP_PAGE'] = TRUE;
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