<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loancontract_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		try {
			$clientWSLoan = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			try {
				$argumentWSLoan = [
					"as_wspass" => $config["WS_STRC_DB"],
					"as_coopid" => $config["COOP_ID"],
					"as_contno" => $dataComing["loancontract_no"],
					"adtm_lastcalint" => date('c')							
				];
				$resultWSLoan = $clientWSLoan->__call("of_computeinterest", array($argumentWSLoan));
				$arrayResult["INT_BALANCE"] = $resultWSLoan->of_computeinterestResult ?? "0.00";
				$fetchLoanRepay = $conoracle->prepare("SELECT lnm.principal_balance
														FROM lncontmaster lnm
														WHERE lnm.loancontract_no = :loancontract_no and lnm.contract_status = 1 and lnm.principal_balance > 0");
				$fetchLoanRepay->execute([':loancontract_no' => $dataComing["loancontract_no"]]);
				$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
				$arrayResult["SUM_BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"] + $arrayResult["INT_BALANCE"],2);
			}catch(Throwable $e){
			}
		}catch(Throwable $e){
		}
		$arrayResult["RESULT"] = TRUE;
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