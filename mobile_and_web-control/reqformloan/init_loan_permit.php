<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != "" && $dataComing["remain_salary"] !=""){
			$oldBal = 0;
			$salary_balance = 0;
			if(file_exists(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php')){
				include(__DIR__.'/../credit/calculate_loan_'.$dataComing["loantype_code"].'.php');
			}else{
				include(__DIR__.'/../credit/calculate_loan_etc.php');
			}	
			$period_payment = $dataComing["request_amt"] / $dataComing["period"];
			$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
			$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
			$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
			
			$period = $dataComing["period"];
			$int_rate = ($rowIntRate["INTEREST_RATE"] / 100);
			$typeCalDate = $func->getConstant("cal_start_pay_date");
			$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
			$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
			if($typeCalDate == "next"){
				$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
			}else{
				$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
			}
			$getPayRound = $conoracle->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
			$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
			$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
			$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
			$period_payment = ($dataComing["request_amt"] * ($int_rate / 12) / (1 - ($payment_per_period)));
			$module = 100 - ($period_payment % 100);
			if($module < 100){
				$period_payment = floor($period_payment + $module);
			}
	
			if($dataComing["remain_salary"] > 0){
				$salary_balance = $dataComing["remain_salary"] - $period_payment;
				if($salary_balance  < 1000){
					$arrayResult['RESPONSE_CODE'] = "WS0120";
					$arrayResult['REVERT_VALUE'] = TRUE;
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}

			if($receive_net < 0){
				$arrayResult['max_sharestk_amt'] = $max_sharestk_amt ;
				$arrayResult['maxloan_amt'] = $maxloan_amt ;
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				$arrayResult["RECEIVE_NET"] = $receive_net;
			}
			$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $arrayResult["RECEIVE_NET"];
			$arrayResult["PERIOD"] = $dataComing["period"];
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
			if($maxloan_amt <= 0){
				$arrayResult['RESPONSE_CODE'] = "WS0084";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$request_amt = $dataComing["request_amt"] ?? $maxloan_amt;
			if($request_amt < $oldBal){
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			
			$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD 
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt >= lnd.MONEY_FROM and :request_amt < lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			//$period_payment = $maxloan_amt / $rowMaxPeriod["MAX_PERIOD"];
			$fetchLoanIntRate = $conoracle->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
			$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
			$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);

			if($dataComing["loantype_code"] == '09' || ($dataComing["loantype_code"] == '51')){			
				$period =  $rowMaxPeriod["MAX_PERIOD"];
			}else{
				$period = $rowMaxPeriod["MAX_PERIOD"];
			}
				$int_rate = ($rowIntRate["INTEREST_RATE"] / 100);
				$typeCalDate = $func->getConstant("cal_start_pay_date");
				$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
			if($typeCalDate == "next"){
				$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
			}else{
				$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
			}
			$getPayRound = $conoracle->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
			$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
			$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
			$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
			$period_payment = ($request_amt * ($int_rate / 12) / (1 - ($payment_per_period)));
			$module = 100 - ($period_payment % 100);
			if($module < 100){
				$period_payment = floor($period_payment + $module);
			}
			
			if($dataComing["loantype_code"] == '51'){
				$fetchContractGroup = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															LEFT JOIN LNLOANTYPE LT ON LT.LOANTYPE_CODE = LM.LOANTYPE_CODE
															WHERE LM.MEMBER_NO = :member_no AND LOANGROUP_CODE = '02' AND LM.LOANTYPE_CODE != '44'
								AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
				$fetchContractGroup->execute([
					':member_no' => $member_no
				]);
				$rowContractGroup = $fetchContractGroup->fetch(PDO::FETCH_ASSOC);

				if(isset($rowContractGroup["LOANCONTRACT_NO"])){
					$arrayResult['RESPONSE_CODE'] = "WS4004";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}				
			}
			

			$arrayResult["DEFAULT_OPTION_PAYTYPE"] = "0";
			$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
			$arrayResult["RECEIVE_NET"] = $receive_net;
			$arrayResult["REQUEST_AMT"] = $request_amt;
			$arrayResult["LOANREQ_AMT_STEP"] = 100;
			//$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $request_amt;
			$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
			$arrayResult["MAX_PERIOD"] = $period;
			$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			$arrayResult["DISABLE_PERIOD"] = FALSE;
			$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/termanduse.html";
			$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
			$arrayResult["NOTE_DESC"] ="หมายเหตุ : กรุณากรอกเงินเดือนคงเหลือที่หัก ค่าหุ้น ค่าเงินกู้สามัญ และค่าใช้จ่ายต่างๆ ";
			$arrayResult["NOTE_DESC_COLOR"] = 'red';
			$arrayResult["REQ_REMAIN_SALARY"] = TRUE;
			$arrayResult["IS_REMAIN_SALARY"] = TRUE;
			$arrayResult["IS_UPLOAD_SALARY"] =  FALSE;			
			$arrayResult["REQ_CITIZEN"] = TRUE;
			$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
			$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
			$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
			$arrayResult["BANK_ACCOUNT_REMARK"] = null;
			$arrayResult['RESULT'] = TRUE;
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
