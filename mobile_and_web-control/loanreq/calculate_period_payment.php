<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','int_rate','period','request_amt','loantype_code','salary_amt','roundpay','maxperiod_payment','maxreceive_amt','diff_old_contract'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$request_net = $dataComing["request_amt"] - $dataComing["diff_old_contract"];
			if($request_net <= 0){
				
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			if($dataComing["maxreceive_amt"] > 0){
				if($request_net > $dataComing["maxreceive_amt"]){
					$arrayResult['RESPONSE_CODE'] = "WS0074";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$structureReqLoanPayment = array();
			$structureReqLoanPayment["calperiod_intrate"] = $dataComing["int_rate"];
			$structureReqLoanPayment["calperiod_maxinstallment"] = $dataComing["period"];
			$structureReqLoanPayment["calperiod_prnamt"] = $dataComing["request_amt"];
			$structureReqLoanPayment["loanpayment_type"] = 2;
			$structureReqLoanPayment["loantype_code"] = $dataComing["loantype_code"];
			$structureReqLoanPayment["period_installment"] = $dataComing["period"];
			$structureReqLoanPayment["period_payment"] = 0;
			$structureReqLoanPayment["progess_flag"] = 0;
			$structureReqLoanPayment["progess_rate"] = 0;
			$structureReqLoanPayment["roundpay_flag"] = '1';
			$structureReqLoanPayment["salary_amount"] = preg_replace('/,/','',$dataComing["salary_amt"]);
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lncalperiod" => $structureReqLoanPayment
				];
				$resultWS = $clientWS->__call("of_calperiodpay", array($argumentWS));
				$responseSoap = $resultWS->astr_lncalperiod;
				$getPayRound = $conoracle->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
				$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
				$pay_period = preg_replace('/,/', '', number_format($responseSoap->period_payment,2));
				if($pay_period > $dataComing["maxperiod_payment"]){
					if(($pay_period - $dataComing["maxperiod_payment"]) > $rowPayRound["PAYROUND_FACTOR"]){
						
						$arrayResult['RESPONSE_CODE'] = "WS0071";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}
				$arrayResult['RECEIVE_AMT'] = $request_net;
				$arrayResult['PERIOD_PAYMENT'] = $pay_period ?? 0;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0062",
					":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."Error => ".($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0062";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0062",
				":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."Error => ".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".$e->getMessage();
			$lib->sendLineNotify($message_error);
			$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_SERVICE"]);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS0062";
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