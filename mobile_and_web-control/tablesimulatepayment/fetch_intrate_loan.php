<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getMembCatCode = $conmssql->prepare("SELECT MEMBCAT_CODE,MEMBTYPE_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMembCatCode->execute([':' => $member_no]);
		$rowMemb = $getMembCatCode->fetch(PDO::FETCH_ASSOC);
		$fetchIntrate = $conmssql->prepare("select lir.interest_rate as INTEREST_RATE,lp.LOANTYPE_DESC,lp.LOANTYPE_CODE from lnloantype lp LEFT JOIN lncfloanintratedet lir
												ON lp.inttabrate_code = lir.loanintrate_code where GETDATE() 
												BETWEEN CONVERT(varchar, lir.effective_date, 23) and CONVERT(varchar, lir.expire_date, 23)");
		$fetchIntrate->execute();
		$arrIntGroup = array();
		while($rowIntrate = $fetchIntrate->fetch(PDO::FETCH_ASSOC)){	
			$getLoantypePeriod = $conmssql->prepare("SELECT MAX_PERIOD FROM lnloantypeperiod 
																						WHERE loantype_code = :loantype_code");
			$getLoantypePeriod->execute([
				':loantype_code' =>  $rowIntrate["LOANTYPE_CODE"]
			]);
			$rowLoanPeriod = $getLoantypePeriod->fetch(PDO::FETCH_ASSOC);
			$getLoantypeCustom = $conmssql->prepare("SELECT lc.MAXLOAN_AMT
																							FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
																							WHERE mb.member_no = :member_no  
																							and LT.LOANTYPE_CODE = :loantype_code
																							and DATEDIFF(month,mb.member_date,getDate()) BETWEEN lc.startmember_time and lc.endmember_time");
			$getLoantypeCustom->execute([
				':member_no' => $member_no,
				':loantype_code' => $rowIntrate["LOANTYPE_CODE"]
			]);
			$rowMaxLoan = $getLoantypeCustom->fetch(PDO::FETCH_ASSOC);
			$arrIntrate = array();
			$arrIntrate["INT_RATE"] = number_format($rowIntrate["INTEREST_RATE"],2);
			$arrIntrate["LOANTYPE_CODE"] = $rowIntrate["LOANTYPE_CODE"];
			$arrIntrate["LOANTYPE_DESC"] = $rowIntrate["LOANTYPE_DESC"];
			$arrIntrate["DEFAULT_PERIOD"] = $rowLoanPeriod["MAX_PERIOD"];
			$arrIntrate["DEFAULT_PAYMENT_TYPE"] = '1';
			$arrIntrate["DEFAULT_AMOUNT"] = $rowMaxLoan["MAXLOAN_AMT"];
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