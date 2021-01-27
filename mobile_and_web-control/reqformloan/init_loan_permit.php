<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != ""){
			$oldBal = 0;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			$receive_net = $dataComing["request_amt"];
			/*if($receive_net < 0){
				$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_LOANREQUEST"][0][$lang_locale];
				$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
				$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
				$arrayResult['CAUTION'] = $arrayCaution;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
				
			}else{
				$arrayResult["RECEIVE_NET"] = $receive_net;
			}*/
			if($max_period == 0){
				$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
				$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
				if($dataComing["option_paytype"] == "0"){
					$typeCalDate = $func->getConstant("process_keep_forward");
					$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
					$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
					if($typeCalDate == "next"){
						$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
					}else{
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
					}
					$period_payment = ($dataComing["request_amt"] / $dataComing["period"]) + (($dataComing["request_amt"] * ($rowIntRate["INTEREST_RATE"] / 100) * $dayOfMonth) / $dayinYear);
					$period_payment = floor($period_payment - ($period_payment % 100));
				}else{
					$period = $max_period == 0 ? (string)$dataComing["period"] : (string)$max_period;
					$int_rate = ($rowIntRate["INTEREST_RATE"] / 100);
					$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
					$period_payment = ($dataComing["request_amt"] * ($int_rate / 12) / (1 - ($payment_per_period)));
					$getPayRound = $conoracle->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
					$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
					$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
					$modFactor = -$rowPayRound["PAYROUND_FACTOR"] ?? 5;
					$roundMod = fmod($period_payment,abs($modFactor));
					if($modFactor > 0){
						if($roundMod > 0){
							$period_payment = $period_payment - $roundMod + abs($modFactor);
						}
					}else if($modFactor < 0){
						if($roundMod > 0){
							$period_payment = $period_payment - $roundMod;
						}
					}
				}
			}
			$arrayResult["RECEIVE_NET"] = $receive_net;
			//$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $dataComing["request_amt"];
			$arrayResult["PERIOD"] = $max_period == 0 ? (string)$dataComing["period"] : (string)$max_period;
			$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$maxloan_amt = 0;
			$oldBal = 0;
			$loanRequest = TRUE;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}
			$maxloan_amt = floor($maxloan_amt - ($maxloan_amt % 100));
			if($maxloan_amt <= 0){
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$request_amt = $dataComing["request_amt"] ?? $maxloan_amt;
			/*if($request_amt < $oldBal){
				$request_amt = $oldBal;
			}*/
			$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD 
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt >= lnd.MONEY_FROM and :request_amt <= lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			if(isset($rowMaxPeriod["MAX_PERIOD"])){
				$getLoanObjective = $conoracle->prepare("SELECT LOANOBJECTIVE_CODE,LOANOBJECTIVE_DESC FROM lnucfloanobjective WHERE loantype_code = :loantype");
				$getLoanObjective->execute([':loantype' => $dataComing["loantype_code"]]);
				$arrGrpObj = array();
				while($rowLoanObj = $getLoanObjective->fetch(PDO::FETCH_ASSOC)){
					$arrObj = array();
					$arrObj["LOANOBJECTIVE_CODE"] = $rowLoanObj["LOANOBJECTIVE_CODE"];
					$arrObj["LOANOBJECTIVE_DESC"] = $rowLoanObj["LOANOBJECTIVE_DESC"];
					$arrGrpObj[] = $arrObj;
				}
				$typeCalDate = $func->getConstant("process_keep_forward");
				if($max_period == 0){
					$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
															ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
															WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE
															ORDER BY lnt.loantype_code");
					$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
					$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
					$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
					$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
					if($typeCalDate == "next"){
						$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
					}else{
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
					}
					$period = $max_period == 0 ? (string)$rowMaxPeriod["MAX_PERIOD"] : (string)$max_period;
					
					$period_payment = ($maxloan_amt / $rowMaxPeriod["MAX_PERIOD"]) + (($maxloan_amt * ($rowIntRate["INTEREST_RATE"] / 100) * $dayOfMonth) / $dayinYear);
					$period_payment = floor($period_payment - ($period_payment % 100));
				}
				if($dataComing["loantype_code"] == '12'){
					$arrPayEqual["VALUE"] = "2";
					$arrPayEqual["DESC"] = "ชำระแค่ดอกเบี้ย";
					$arrGrpPayType[] = $arrPayEqual;
					$arrayResult["DEFAULT_OPTION_PAYTYPE"] = "2";
					$arrayResult["DISABLE_PERIOD"] = TRUE;
				}else{
					$arrPayPrin["VALUE"] = "0";
					$arrPayPrin["DESC"] = "คงต้น";
					$arrGrpPayType[] = $arrPayPrin;
					$arrPayEqual["VALUE"] = "1";
					$arrPayEqual["DESC"] = "คงยอด";
					$arrGrpPayType[] = $arrPayEqual;
					$arrayResult["DEFAULT_OPTION_PAYTYPE"] = "0";
				}
				$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/loan_termanduse.html";
				//$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
				$arrayResult["LOANREQ_AMT_STEP"] = 100;
				$arrayResult["RECEIVE_NET"] = $maxloan_amt;
				$arrayResult["REQUEST_AMT"] = (string)$request_amt;
				$arrayResult["PAY_DATE"] = $lib->convertdate(date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d')))),'d M Y');
				//$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $period;
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
				$arrayResult["OPTION_PAYTYPE"] = $arrGrpPayType;
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				$arrayResult["REQ_SALARY"] = TRUE;
				$arrayResult["REQ_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = TRUE;
				$arrayResult["IS_UPLOAD_SALARY"] = TRUE;
				$arrayResult['OBJECTIVE'] = $arrGrpObj;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0088";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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