<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','int_rate','payment_sumbalance','calint_type','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$request_date = $dataComing['request_date'];
		$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
		$pay_date = date("Y-m-t", strtotime('last day of '.$cal_start_pay_date.' month',strtotime($lib->convertdate($request_date,'y-N-d'))));
		$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
		$payment_sumbalance = (float) preg_replace('/,/','',$dataComing['payment_sumbalance']);
		$int_rate = $dataComing["int_rate"]/100;
		$calint_type = $dataComing["calint_type"];
		$arrPayment = array();
		$lastDateofMonth = strtotime(date('M Y',strtotime($pay_date)));
		$payment_per_period = 0;
		$period_payment = isset($dataComing['period_payment']) ? (float) preg_replace('/,/','',$dataComing['period_payment']) : 0;
		
		if($lib->checkCompleteArgument(['period'],$dataComing)){
			$period = $dataComing["period"];
		}else{
			if($calint_type === "1"){ // คงต้น
				$period = ceil($payment_sumbalance / $period_payment);
			}else{ 
				$period = 0;
				$amt = $payment_sumbalance;
				$paymentPerPeriod = $period_payment;
				$princPerPeriod = 0;
				while ($amt > 0) {
					if($period == 0){
						if($cal_start_pay_date == "next"){
							$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
						}else{
							$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						}
					}else {
						$lastDate = date('Y-m-t',strtotime("+".($period)." months",$lastDateofMonth));
						$dayOfMonth = date('d',strtotime($lastDate));
					}
					
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
			if($calint_type === "1"){ // คงต้น
				$pay_period = $payment_sumbalance / $period;
			}else{ 
				$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
				$pay_period = round($payment_sumbalance * ($int_rate / 12) / (1 - ($payment_per_period)));
			}
		}
		
		for($i = 1;$i <= $period;$i++){
			$arrPaymentPerPeriod = array();
			if($calint_type === "1"){ // คงต้น
				if($i == 1){
					if($cal_start_pay_date == "next"){
						$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
					}else{
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
					}
					$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
				}else {
					$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
					$dayOfMonth = date('d',strtotime($lastDate));
				}
				
				$period_int = ($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear);
				if (($payment_sumbalance) < $pay_period) {
				  $prn_amount = $payment_sumbalance;
				}else{
					$prn_amount = $pay_period;
				}
				
				$payment_sumbalance = $payment_sumbalance - $prn_amount;
				
				$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'D m Y');
				$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
				$arrPaymentPerPeriod["DAYS"] = $dayOfMonth;
				$arrPaymentPerPeriod["PERIOD"] = $i;
				$arrPaymentPerPeriod["INTEREST"] = number_format(round($period_int),2);
				$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($prn_amount+round($period_int),2);
				$arrPaymentPerPeriod["PRINCIPAL_BALANCE"] = number_format($payment_sumbalance,2);
				
			}else if($calint_type === "2"){ // คงยอด ต้น + ดอก เท่ากันทุกเดือน
				if($i == 1){
					if($cal_start_pay_date == "next"){
						$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
					}else{
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
					}
					$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
				}else {
					$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
					$dayOfMonth = date('d',strtotime($lastDate));
				}
				
				$period_int = round($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear);
				$prn_amount = $pay_period - $period_int;
			
				
				if (($payment_sumbalance) < $pay_period) {
				  $prn_amount = $payment_sumbalance;
				}
					
				$payment_sumbalance = $payment_sumbalance - ($prn_amount);
				
				$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'D m Y');
				$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
				$arrPaymentPerPeriod["DAYS"] = $dayOfMonth;
				$arrPaymentPerPeriod["PERIOD"] = $i;
				$arrPaymentPerPeriod["INTEREST"] = number_format(round($period_int),2);
				$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($prn_amount+$period_int,2);
				$arrPaymentPerPeriod["PRINCIPAL_BALANCE"] = number_format($payment_sumbalance,2);
			}
			$arrPayment[] = $arrPaymentPerPeriod;
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