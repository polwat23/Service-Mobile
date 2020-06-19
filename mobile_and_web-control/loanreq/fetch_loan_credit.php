<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$structureReqLoan = array();
			$structureReqLoan["coop_id"] = $config["COOP_ID"];
			$structureReqLoan["member_no"] = $member_no;
			$structureReqLoan["loantype_code"] = $dataComing["loantype_code"];
			$structureReqLoan["operate_date"] = date("c");
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"atr_lnatm" => $structureReqLoan
				];
				$resultWS = $clientWS->__call("of_getloanpermiss_IVR", array($argumentWS));
				$responseSoap = $resultWS->atr_lnatm;
				$arrayResult['dddd'] = $responseSoap;
				$arrayResult['ACCOUNT_RECEIVE'] = $responseSoap->account_id;
				$arrayResult['CONTRACT_CLR'] = $responseSoap->contclr_no;
				$arrayResult['PRINBAL_CLR'] = $responseSoap->prinbal_clr;
				$arrayResult['INT_CLR'] = $responseSoap->intpayment_clr;
				$arrayResult['LOANPERMIT_AMT'] = $responseSoap->loanpermiss_amt;
				$arrayResult['SALARY_AMT'] = $responseSoap->approve_amt;
				$arrayResult['DIFF_OLD_CONTRACT'] = $responseSoap->prinbal_clr + $responseSoap->intpayment_clr;
				$structureReqLoanPayment = array();
				$structureReqLoanPayment["calperiod_intrate"] = $dataComing["int_rate"];
				$structureReqLoanPayment["calperiod_maxinstallment"] = $dataComing["max_period"];
				$structureReqLoanPayment["calperiod_prnamt"] = $responseSoap->loanpermiss_amt;
				$structureReqLoanPayment["loanpayment_type"] = 2;
				$structureReqLoanPayment["loantype_code"] = $dataComing["loantype_code"];
				$structureReqLoanPayment["period_installment"] = $dataComing["max_period"];
				$structureReqLoanPayment["period_payment"] = 0;
				$structureReqLoanPayment["progess_flag"] = 0;
				$structureReqLoanPayment["progess_rate"] = 0;
				$structureReqLoanPayment["salary_amount"] = $responseSoap->approve_amt;
				try {
					$argumentWS_Credit = [
						"as_wspass" => $config["WS_STRC_DB"],
						"astr_lncalperiod" => $structureReqLoanPayment
					];
					$resultWS_Credit = $clientWS->__call("of_calperiodpay", array($argumentWS_Credit));
					$responseSoap_Credit = $resultWS_Credit->astr_lncalperiod;
					$arrayResult['PERIOD_PAYMENT'] = $responseSoap_Credit->period_payment ?? 0;
					$arrayResult['MAXRECEIVE_AMT'] = $responseSoap->maxreceive_amt > 0 ? $responseSoap->maxreceive_amt : $responseSoap->loanpermiss_amt;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}catch(SoapFault $e){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS0062",
						":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult['RESPONSE_CODE'] = "WS0062";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0058",
					":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0058";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0058",
				":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." คำนวณสิทธิ์กู้ไม่ได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".json_encode($e);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS0058";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>