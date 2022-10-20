<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','int_rate','payment_sumbalance','calint_type','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$getLoanGroup = $conmssql->prepare("SELECT LOANGROUP_CODE FROM lnloantype WHERE loantype_code = :loantype_code");
		$getLoanGroup->execute([
			':loantype_code' => $dataComing["loantype_code"]
		]);
		$rowLoanGroup = $getLoanGroup->fetch(PDO::FETCH_ASSOC);
		$request_date = date("Y-m-d");
		//วันหยุด
		$holiday_date = "2022-06-03";
		//วันที่เลื่อน
		$holiday_change_date = "2022-06-02";
		if($rowLoanGroup["LOANGROUP_CODE"] == '01'){
			if(date('w') > 3 || date('w') == 0){
				$request_date = date( 'Y-m-d', strtotime( 'friday next week' ) );
				if($request_date == $holiday_date){
					$request_date = $holiday_change_date;
				}
			}else{
				$request_date = date( 'Y-m-d', strtotime( 'friday this week' ) );
				if($request_date == $holiday_date){
					$request_date = $holiday_change_date;
				}
			}
		}else{
			if(date('d')>=1 && date('d')<=10){
				$middle_month = new DateTime(date('Y')."-".date('m')."-20");
				$middle_month = $middle_month->format('Y-m-d');
				$getLoanPayDate = $conmysql->prepare("SELECT loanpaydate FROM gcconstantloanpaydate WHERE is_use = '1' AND :middle_month < loanpaydate ORDER BY loanpaydate ASC LIMIT 1");
				$getLoanPayDate->execute([':middle_month' => $middle_month]);
				$rowLoanPayDate = $getLoanPayDate->fetch(\PDO::FETCH_ASSOC);
				if(isset($rowLoanPayDate["loanpaydate"])){
					if(date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 0 || date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 6){
						$request_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]." friday this week"));
					}else{
						$request_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]));
					}
				}
			}else if(date('d')>=20){
				#mid month
				$mid_date = new DateTime(date('Y')."-".date('m')."-15");
				$mid_date->modify('+1 month');
				$mid_date = $mid_date->format('Y-m-d');
				
				$mid_date_from = new DateTime(date('Y')."-".date('m')."-01");
				$mid_date_from->modify('+1 month');
				$mid_date_from = $mid_date_from->format('Y-m-d');
				$mid_date_to = new DateTime(date('Y')."-".date('m')."-20");
				$mid_date_to->modify('+1 month');
				$mid_date_to = $mid_date_to->format('Y-m-d');
				
				$getLoanPayDate = $conmysql->prepare("SELECT loanpaydate FROM gcconstantloanpaydate 
								WHERE is_use = '1' AND :mid_date_from <= loanpaydate AND :mid_date_to >= loanpaydate 
								ORDER BY loanpaydate ASC LIMIT 1");
				$getLoanPayDate->execute([
					':mid_date_from' => $mid_date_from,
					':mid_date_to' => $mid_date_to
				]);
				$rowLoanPayDate = $getLoanPayDate->fetch(\PDO::FETCH_ASSOC);
				
				if(isset($rowLoanPayDate["loanpaydate"])){
					if(date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 0 || date('w', strtotime($rowLoanPayDate["loanpaydate"])) == 6){
						$request_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]." friday this week"));
					}else{
						$request_date = date('Y-m-d', strtotime($rowLoanPayDate["loanpaydate"]));
					}
				}else{
					if(date('w', strtotime($mid_date)) == 0 || date('w', strtotime($mid_date)) == 6){
						$request_date = date('Y-m-d', strtotime($mid_date." friday this week"));
					}else{
						$request_date = date('Y-m-d', strtotime($mid_date));
					}
				}
			}
		}
		
		$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
		$pay_date = date("Y-m-d", strtotime($dataComing['request_date']));
		$payment_sumbalance = (float) preg_replace('/,/','',$dataComing['payment_sumbalance']);
		$int_rate = $dataComing["int_rate"]/100;
		$calint_type = $dataComing["calint_type"];
		$arrPayment = array();
		$lastDateofMonth = strtotime(date("Y-m-d",strtotime($pay_date)));
		$payment_per_period = 0;
		$sumInt = 0;
		$sumPayment = 0;
		$period_payment = isset($dataComing['period_payment']) ? (float) preg_replace('/,/','',$dataComing['period_payment']) : 0;
		$getRoundType = $conmssql->prepare("SELECT ROUND_TYPE FROM CMROUNDMONEY WHERE APPLGROUP_CODE = 'LON'");
		$getRoundType->execute();
		$rowRoundType = $getRoundType->fetch(PDO::FETCH_ASSOC);
		$getConstantYear = $conmssql->prepare("SELECT DAYINYEAR FROM LNLOANCONSTANT");
		$getConstantYear->execute();
		$rowConstant = $getConstantYear->fetch(PDO::FETCH_ASSOC);
		if($rowConstant["DAYINYEAR"] == 0){
			$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
		}else{
			$dayinYear = $rowConstant["DAYINYEAR"];
		}
		$getPayRound = $conmssql->prepare("SELECT PAYROUND_FACTOR FROM lnloantype WHERE loantype_code = :loantype_code");
		$getPayRound->execute([':loantype_code' => $dataComing["loantype_code"]]);
		$rowPayRound = $getPayRound->fetch(PDO::FETCH_ASSOC);
		if($lib->checkCompleteArgument(['period'],$dataComing)){
			$period = $dataComing["period"];
		}else{
			if($calint_type === "1"){ // 
				$period = ceil($payment_sumbalance / $period_payment);
			}else{ 
				$period = 0;
				$amt = $payment_sumbalance;
				$paymentPerPeriod = $period_payment;
				$princPerPeriod = 0;
				while ($amt > 0) {
					if($period == 0){
						if($cal_start_pay_date == "next"){
							//$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
							$dateFrom = new DateTime(date('d-m-Y',strtotime($request_date)));
							$dateTo = new DateTime(date('d-m-Y',strtotime($pay_date)));
							$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
						}else{
							//$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
							$dateFrom = new DateTime(date('d-m-Y',strtotime($request_date)));
							$dateTo = new DateTime(date('d-m-Y',strtotime($pay_date)));
							$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
						}
						$lastDate = date('Y-m-d',strtotime("+".($period)." months",$lastDateofMonth));
					}else {
						$lastDate = date('Y-m-d',strtotime("+".($period)." months",strtotime(date("Y-m-d",strtotime($pay_date)))));
						$dateFrom = new DateTime(date('Y-m-d',strtotime("+".($period-1)." months",strtotime(date("Y-m-d",strtotime($pay_date))))));
						$dateTo = new DateTime(date('Y-m-d',strtotime("+".($period)." months",strtotime(date("Y-m-d",strtotime($pay_date))))));
						$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
						//$dayOfMonth = date('d',strtotime($lastDate));
					}
					/*if($period == 0){
						if($cal_start_pay_date == "next"){
							$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
						}else{
							$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						}
					}else {
						$lastDate = date('Y-m-t',strtotime("+".($period)." months",$lastDateofMonth));
						$dayOfMonth = date('d',strtotime($lastDate));
					}*/
					
					$intPerPeroid = ($amt * ($int_rate) * $dayOfMonth) / $dayinYear;

					if ($amt < $paymentPerPeriod) {
						$princPerPeriod = $amt;
						$paymentPerPeriod = $princPerPeriod + $intPerPeroid;
					} else {
						$princPerPeriod = $paymentPerPeriod - $intPerPeroid;
					}

					$amt = $amt - $princPerPeriod;
					$period++;
				}
			}
		}
		if($lib->checkCompleteArgument(['period_payment'],$dataComing)){
			$pay_period = $period_payment;
		}else{
			if($calint_type === "1"){ // 
				$pay_period = $payment_sumbalance / $period;
			}else{ 
				$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
				$pay_period = ($payment_sumbalance * ($int_rate / 12) / (1 - ($payment_per_period)));
				
				$modFactor = $rowPayRound["PAYROUND_FACTOR"] ?? 5;
				$roundMod = fmod($pay_period,abs($modFactor));
				if($modFactor > 0){
					if($roundMod > 0){
						$pay_period = $pay_period - $roundMod + abs($modFactor);
					}
				}else if($modFactor < 0){
					if($roundMod > 0){
						$pay_period = $pay_period - $roundMod;
					}
				}
			}
		}
		
		for($i = 1;$i <= $period;$i++){
			$arrPaymentPerPeriod = array();
			if($calint_type === "1"){ // 
				if($i == 1){
					if($cal_start_pay_date == "next"){
						//$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
						$dateFrom = new DateTime(date('d-m-Y',strtotime($request_date)));
						$dateTo = new DateTime(date('d-m-Y',strtotime($pay_date)));
						$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
					}else{
						//$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						$dateFrom = new DateTime(date('d-m-Y',strtotime($request_date)));
						$dateTo = new DateTime(date('d-m-Y',strtotime($pay_date)));
						$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
					}
					$lastDate = date('Y-m-d',strtotime("+".($i-1)." months",$lastDateofMonth));
				}else {
					$lastDate = date('Y-m-d',strtotime("+".($i-1)." months",strtotime(date("Y-m-d",strtotime($pay_date)))));
					$dateFrom = new DateTime(date('Y-m-d',strtotime("+".($i-2)." months",strtotime(date("Y-m-d",strtotime($pay_date))))));
					$dateTo = new DateTime(date('Y-m-d',strtotime("+".($i-1)." months",strtotime(date("Y-m-d",strtotime($pay_date))))));
					$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
					//$dayOfMonth = date('d',strtotime($lastDate));
				}
				if($rowConstant["DAYINYEAR"] == 0){
					$dayinYear = $lib->getnumberofYear(date('Y',strtotime($lastDate)));
				}else{
					$dayinYear = $rowConstant["DAYINYEAR"];
				}
				$period_int = $lib->roundDecimal($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear,$rowRoundType["ROUND_TYPE"]);
				if (($payment_sumbalance) < $pay_period) {
					$prn_amount = $payment_sumbalance;
				}else{
					$prn_amount = ceil($pay_period);
				}
				$periodPayment = $prn_amount + $period_int;
				$payment_sumbalance = $payment_sumbalance - $prn_amount;
				$sumInt += $period_int;
				$sumPayment += $periodPayment;
				$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'D m Y');
				$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
				$arrPaymentPerPeriod["DAYS"] = $dayOfMonth;
				$arrPaymentPerPeriod["PERIOD"] = $i;
				$arrPaymentPerPeriod["INTEREST"] = number_format($period_int,2);
				$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($periodPayment,2);
				$arrPaymentPerPeriod["PRINCIPAL_BALANCE"] = number_format($payment_sumbalance,2);
				
			}else if($calint_type === "2"){ // ʹ  + ͡ ҡѹء͹
				if($i == 1){
					if($cal_start_pay_date == "next"){
						//$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
						$dateFrom = new DateTime(date('d-m-Y',strtotime($request_date)));
						$dateTo = new DateTime(date('d-m-Y',strtotime($pay_date)));
						$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
					}else{
						//$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						$dateFrom = new DateTime(date('d-m-Y',strtotime($request_date)));
						$dateTo = new DateTime(date('d-m-Y',strtotime($pay_date)));
						$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
					}
					$lastDate = date('Y-m-d',strtotime("+".($i-1)." months",$lastDateofMonth));
				}else {
					$lastDate = date('Y-m-d',strtotime("+".($i-1)." months",strtotime(date("Y-m-d",strtotime($pay_date)))));
					$dateFrom = new DateTime(date('Y-m-d',strtotime("+".($i-2)." months",strtotime(date("Y-m-d",strtotime($pay_date))))));
					$dateTo = new DateTime(date('Y-m-d',strtotime("+".($i-1)." months",strtotime(date("Y-m-d",strtotime($pay_date))))));
					$dayOfMonth = $dateTo->diff($dateFrom)->format('%a');
					//$dayOfMonth = date('d',strtotime($lastDate));
				}
				if($rowConstant["DAYINYEAR"] == 0){
					$dayinYear = $lib->getnumberofYear(date('Y',strtotime($lastDate)));
				}else{
					$dayinYear = $rowConstant["DAYINYEAR"];
				}
				$period_int = $lib->roundDecimal($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear,$rowRoundType["ROUND_TYPE"]);
				$prn_amount = $pay_period - $period_int;
				
				
				if (($payment_sumbalance) < $pay_period) {
				  $prn_amount = $payment_sumbalance;
				}
				$periodPaymentRaw = $prn_amount + $period_int;
				if($lib->checkCompleteArgument(['period_payment'],$dataComing)){
					$periodPayment = $periodPaymentRaw;
				}else{
					if($i == $period){
						$periodPayment = $periodPaymentRaw;
					}else{
						$modFactor = $rowPayRound["PAYROUND_FACTOR"] ?? 5;
						$roundMod = fmod($periodPaymentRaw,abs($modFactor));
						if($modFactor > 0){
							if($roundMod > 0){
								$periodPayment = $periodPaymentRaw - $roundMod + abs($modFactor);
							}else{
								$periodPayment = $periodPaymentRaw;
							}
						}else if($modFactor < 0){
							if($roundMod > 0){
								$periodPayment = $periodPaymentRaw - $roundMod;
							}else{
								$periodPayment = $periodPaymentRaw;
							}
						}else{
							$periodPayment = $periodPaymentRaw;
						}
					}
				}
				$payment_sumbalance = $payment_sumbalance - ($prn_amount);
				$sumInt += $period_int;
				$sumPayment += $periodPayment;
				$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'D m Y');
				$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
				$arrPaymentPerPeriod["DAYS"] = $dayOfMonth;
				$arrPaymentPerPeriod["PERIOD"] = $i;
				$arrPaymentPerPeriod["INTEREST"] = number_format($period_int,2);
				$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($periodPayment,2);
				$arrPaymentPerPeriod["PRINCIPAL_BALANCE"] = number_format($payment_sumbalance,2);
			}
			if($prn_amount > 0){
				$arrPayment[] = $arrPaymentPerPeriod;
			}
		}
		include(__DIR__.'/show_table_payment.php');
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
		":error_desc" => " Argument ú "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = " ".$filename."  Argument ú "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>