<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$fetchIntrate = $conoracle->prepare("SELECT LOANTYPE_DESC,LOANTYPE_CODE,CONTINTTABLE_CODE FROM LNLOANTYPE");
		$fetchIntrate->execute();
		$arrIntGroup = array();
		while($rowLoantype = $fetchIntrate->fetch(PDO::FETCH_ASSOC)){
			$getIntrate = $conoracle->prepare("SELECT INTEREST_RATE FROM (SELECT INTEREST_RATE FROM lnloanintrate WHERE loantype_code = :loantype_code and effective_date IS NOT NULL 
												and to_char(sysdate,'YYYY-MM-DD') > to_char(effective_date,'YYYY-MM-DD') ORDER BY effective_date DESC ) WHERE rownum <= 1");
			$getIntrate->execute([':loantype_code' => $rowLoantype["CONTINTTABLE_CODE"]]);
			$rowIntrate = $getIntrate->fetch(PDO::FETCH_ASSOC);
			$arrIntrate = array();
			$arrIntrate["INT_RATE"] = "".($rowIntrate["INTEREST_RATE"] * 100)."";
			$arrIntrate["LOANTYPE_CODE"] = $rowLoantype["LOANTYPE_CODE"];
			$arrIntrate["LOANTYPE_DESC"] = $rowLoantype["LOANTYPE_DESC"];
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