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
			$period_payment = $dataComing["request_amt"] / $dataComing["period"];
			$fetchLoanIntRate = $conmssql->prepare("SELECT lnd.interest_rate FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and GETDATE() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
			$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
			$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
			if($dataComing["option_paytype"] == "0"){
				$typeCalDate = $func->getConstant("cal_start_pay_date");
				$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
				if($typeCalDate == "next"){
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
				}else{
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
				}
				$period_payment = ($dataComing["request_amt"] / $dataComing["period"]);
				$module = 10 - ($period_payment % 10);
				if($module < 10){
					$period_payment = floor($period_payment + $module);
				}
			}else if($dataComing["option_paytype"] == "1"){
				$period = $dataComing["period"];
				$int_rate = ($rowIntRate["interest_rate"] / 100);
				$typeCalDate = $func->getConstant("cal_start_pay_date");
				$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
				if($typeCalDate == "next"){
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
				}else{
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
				}
				$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
				$period_payment = ($dataComing["request_amt"] * ($int_rate / 12) / (1 - ($payment_per_period)));
				$module = 10 - ($period_payment % 10);
				if($module < 10){
					$period_payment = floor($period_payment + $module);
				}
			}
			$receive_net = $dataComing["request_amt"] - $oldBal;
			if($receive_net < 0){
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				$arrayResult["RECEIVE_NET"] = $receive_net;
			}
			
			$arrayResult["PERIOD"] = $dataComing["period"];
			if($dataComing["loantype_code"] != '23'){
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			}
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
			$getLoanObjective = $conmssql->prepare("SELECT LOANOBJECTIVE_CODE,LOANOBJECTIVE_DESC FROM lnucfloanobjective WHERE loantype_code = :loantype");
			$getLoanObjective->execute([':loantype' => $dataComing["loantype_code"]]);
			$arrGrpObj = array();
			while($rowLoanObj = $getLoanObjective->fetch(PDO::FETCH_ASSOC)){
				$arrObj = array();
				$arrObj["LOANOBJECTIVE_CODE"] = $rowLoanObj["LOANOBJECTIVE_CODE"];
				$arrObj["LOANOBJECTIVE_DESC"] = $rowLoanObj["LOANOBJECTIVE_DESC"];
				$arrGrpObj[] = $arrObj;
			}
			$getMaxPeriod = $conmssql->prepare("SELECT max_period 
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt_from >= lnd.MONEY_FROM and :request_amt_to < lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt_from' => $maxloan_amt,
				':request_amt_to' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			if(isset($rowMaxPeriod["max_period"])){
				$fetchLoanIntRate = $conmssql->prepare("SELECT lnd.interest_rate FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and GETDATE() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
				$fetchLoanIntRate->execute([':loantype_code' => $dataComing["loantype_code"]]);
				$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
				$period = $rowMaxPeriod["max_period"];
				$int_rate = ($rowIntRate["interest_rate"] / 100);
				$typeCalDate = $func->getConstant("cal_start_pay_date");
				$pay_date = date("Y-m-t", strtotime('last day of '.$typeCalDate.' month',strtotime(date('Y-m-d'))));
				$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
				if($typeCalDate == "next"){
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t") - date("d"));
				}else{
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d");
				}
				$period_payment = ($request_amt  / $period);
				$module = 10 - ($period_payment % 10);
				if($module < 10){
					$period_payment = floor($period_payment + $module);
				}

				$typeCalDate = $func->getConstant("cal_start_pay_date");
				
				$arrPayPrin["VALUE"] = "0";
				$arrPayPrin["DESC"] = "คงต้น";
				$arrGrpPayType[] = $arrPayPrin;
				$arrPayEqual["VALUE"] = "1";
				if($dataComing["loantype_code"] == '20'){
					$arrPayEqual["DESC"] = "คงยอด";
					$arrGrpPayType[] = $arrPayEqual;
				}
				$arrayResult["DEFAULT_OPTION_PAYTYPE"] = "0";
				
				$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
				$arrayResult["RECEIVE_NET"] = $receive_net;
				$arrayResult["REQUEST_AMT"] = $request_amt;
				$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrayResult["MAX_PERIOD"] = $rowMaxPeriod["max_period"];
				$arrayResult["OPTION_PAYTYPE"] = $arrGrpPayType;
				
				if($dataComing["loantype_code"] != '23'){
					$arrayResult["PERIOD_PAYMENT"] = $period_payment;
				}
				$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/termanduse.html";
				$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
				$arrayResult["REQ_SALARY"] = TRUE;  ///TRUE
				$arrayResult["REQ_REMAIN_SALARY"] = TRUE;
				$arrayResult["REQ_CITIZEN"] = FALSE;
				$arrayResult["REQ_BANK_ACCOUNT"] = FALSE;
				$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
				$arrayResult["IS_UPLOAD_SALARY"] = TRUE; 
				$arrayResult["IS_REMAIN_SALARY"] = TRUE;
				$arrayResult["IS_BANK_ACCOUNT"] = FALSE;
				$arrayResult["BANK_ACCOUNT_REMARK"] = null;
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