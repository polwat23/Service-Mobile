<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code','amount'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$fetchIntrate = $conmssqlcoop->prepare("SELECT it.Interest as INTEREST_RATE
											FROM cointerestrate it
											WHERE it.Interest IS NOT NULL and it.type = :loantype_code and :amount BETWEEN it.loan_min and it.loan_max");
		$fetchIntrate->execute([
			':loantype_code' => $dataComing["loantype_code"],
			':amount' => $dataComing["amount"]
		]);
		$rowIntrate = $fetchIntrate->fetch(PDO::FETCH_ASSOC);
		$getLimitMax = $conmssqlcoop->prepare("SELECT limit FROM cointerestrate_desc WHERE type = :loantype_code");
		$getLimitMax->execute([':loantype_code' => $dataComing["loantype_code"]]);
		$rowLimit = $getLimitMax->fetch(PDO::FETCH_ASSOC);
		if($dataComing["amount"] > $rowLimit["limit"] && $rowLimit["limit"] != 0){
			$arrayResult['AMOUNT'] = number_format($rowLimit["limit"],2);
		}
		$arrayResult['INT_RATE'] = number_format($rowIntrate["INTEREST_RATE"],2);
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