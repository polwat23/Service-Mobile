<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		
		$arrCanCal = array();
		$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_loanrequest = '1'");
		$fetchLoanCanCal->execute();
		while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
			$arrCanCal[] = $rowCanCal["loantype_code"];
		}
	
		$fetchIntrate = $conmssql->prepare("select lir.interest_rate as INTEREST_RATE,lp.LOANTYPE_DESC,lp.LOANTYPE_CODE from lnloantype lp LEFT JOIN lncfloanintratedet lir
												ON lp.inttabrate_code = lir.loanintrate_code 
												where lp.loantype_code IN(".implode(',',$arrCanCal).") AND GETDATE() 
												BETWEEN CONVERT(varchar, lir.effective_date, 23) and CONVERT(varchar, lir.expire_date, 23)");
		$fetchIntrate->execute();
		$arrIntGroup = array();
		while($rowIntrate = $fetchIntrate->fetch(PDO::FETCH_ASSOC)){
			$arrIntrate = array();
			$arrIntrate["INT_RATE"] = number_format($rowIntrate["INTEREST_RATE"],2);
			$arrIntrate["LOANTYPE_CODE"] = $rowIntrate["LOANTYPE_CODE"];
			$arrIntrate["LOANTYPE_DESC"] = $rowIntrate["LOANTYPE_DESC"];
			$arrIntGroup[] = $arrIntrate;
		}
		$arrayResult['INT_RATE'] = $arrIntGroup;
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