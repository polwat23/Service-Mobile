<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code','max_period','int_rate'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkCreditLoan = $conoracle->prepare("SELECT contcredit_no FROM lncontcredit WHERE loantype_code = :loantype_code and member_no = :member_no and credit_status = '1'");
		$checkCreditLoan->execute([
			':loantype_code' => $dataComing["loantype_code"],
			':member_no' => $member_no
		]);
		$rowCreditLoan = $checkCreditLoan->fetch(PDO::FETCH_ASSOC);
		if(empty($rowCreditLoan["CONTCREDIT_NO"]) || $rowCreditLoan["CONTCREDIT_NO"] == ""){
			$arrayResult['RESPONSE_CODE'] = "WS0073";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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
				$resultWS = $clientWS->__call("of_initloanrequest_mobile_atm", array($argumentWS));
				$responseSoap = $resultWS->atr_lnatm;
				if($responseSoap->msg_status == '000'){
					$arrayResult['ACCOUNT_RECEIVE'] = $lib->formataccount($responseSoap->account_id,$func->getConstant('dep_format'));
					$arrayResult['ACCOUNT_RECEIVE_HIDDEN'] = $lib->formataccount_hidden($arrayResult['ACCOUNT_RECEIVE'],$func->getConstant('hidden_dep'));
					$arrayResult['CONTRACT_CLR'] = $responseSoap->contclr_no;
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
					$structureReqLoanPayment["period_lastpayment"] = $dataComing["max_period"];
					$structureReqLoanPayment["period_payment"] = 0;
					$structureReqLoanPayment["progess_flag"] = 0;
					$structureReqLoanPayment["progess_flag"] = 0;
					$structureReqLoanPayment["roundpay_flag"] = $responseSoap->roundpay_factor;
					$structureReqLoanPayment["salary_amount"] = $responseSoap->approve_amt;
					try {
						$argumentWS_Credit = [
							"as_wspass" => $config["WS_STRC_DB"],
							"astr_lncalperiod" => $structureReqLoanPayment
						];
						$resultWS_Credit = $clientWS->__call("of_calperiodpay", array($argumentWS_Credit));
						$responseSoap_Credit = $resultWS_Credit->astr_lncalperiod;
						if($responseSoap_Credit->period_payment > $responseSoap->maxperiod_payment){
							$arrayResult = array();
							$arrayResult['RESPONSE_CODE'] = "WS0071";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
						if($responseSoap->maxperiod_payment == 0){
							$arrayResult = array();
							$arrayResult['RESPONSE_CODE'] = "WS0072";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
						$arrayResult['ROUNDPAY'] = $responseSoap->roundpay_factor;
						$arrayResult['MAXPERIOD_PAYMENT'] = $responseSoap->maxperiod_payment ?? 0;
						$arrayResult['PERIOD_PAYMENT'] = round($responseSoap_Credit->period_payment,2) ?? 0;
						$arrayResult['MAXRECEIVE_AMT'] = $responseSoap->maxreceive_amt;
						$arrayResult['RECEIVE_AMT'] = round($responseSoap->loanpermiss_amt - $arrayResult['DIFF_OLD_CONTRACT'],2);
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
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
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS0058",
						":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."Error => ".$responseSoap->msg_output,
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult['RESPONSE_CODE'] = "WS0058";
					if(isset($responseSoap->msg_output) && $responseSoap->msg_output != ""){
						$arrayResult['RESPONSE_MESSAGE'] = $responseSoap->msg_output;
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0058",
					":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n"."Error => ".($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0058";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0058",
				":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้เพราะต่อ Service เงินกู้ไม่ได้"."\n".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." คำนวณสิทธิ์กู้ไม่ได้เพราะต่อ Service เงินกู้ไม่ได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".$e->getMessage();
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