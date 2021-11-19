<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','int_rate','payment_sumbalance','calint_type','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$request_date = date('Y-m-d');//$dataComing['request_date'];
		$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
		$pay_date = date("Y-m-t", strtotime($request_date));
		$payment_sumbalance = (float) preg_replace('/,/','',$dataComing['payment_sumbalance']);
		$int_rate = $dataComing["int_rate"] == '0' ? 0 : $dataComing["int_rate"]/100;
		$calint_type = $dataComing["calint_type"];
		$arrPayment = array();
		$lastDateofMonth = strtotime(date('M Y',strtotime($pay_date)));
		$payment_per_period = 0;
		$sumInt = 0;
		$sumPayment = 0;
		$getAmountPaid = $conmssqlcoop->prepare("SELECT 
													(CASE WHEN PAYDATE_1 IS NULL OR PAYDATE_1 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_2 IS NULL OR PAYDATE_2 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_3 IS NULL OR PAYDATE_3 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_4 IS NULL OR PAYDATE_4 = 0 THEN 0 ELSE 1 END
													+
													CASE WHEN PAYDATE_5 IS NULL OR PAYDATE_5 = 0 THEN 0 ELSE 1 END)
													AS COUNT_PAID
													FROM cocooptation co 
													LEFT JOIN cocompany com ON co.COMPANY = com.COMPANY
													WHERE co.member_id = :member_no");
		$getAmountPaid->execute([':member_no' => $member_no]);
		$rowPaid = $getAmountPaid->fetch(PDO::FETCH_ASSOC);
		$period_payment = isset($dataComing['period_payment']) ? (float) preg_replace('/,/','',$dataComing['period_payment']) : 0;
		
		$dayinYear = 365;
		if($rowPaid == 1){
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
							$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
							$dayOfMonth++;
						}else {
							$lastDate = date('Y-m-t',strtotime("+".($period)." months",$lastDateofMonth));
							$dayOfMonth = date('d',strtotime($lastDate));
						}

						if($int_rate == 0){
							$intPerPeroid = 0;
						}else{
							$intPerPeroid = ($amt * ($int_rate) * $dayOfMonth) / $dayinYear;
						}

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
					if($int_rate == 0){
						$pay_period = $payment_sumbalance / $period;
					}else{
						$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
						$pay_period = ($payment_sumbalance * ($int_rate / 12) / (1 - ($payment_per_period)));
					}
					/*$modFactor = $rowPayRound["PAYROUND_FACTOR"] ?? 5;
					$roundMod = fmod($pay_period,abs($modFactor));
					if($modFactor > 0){
						if($roundMod > 0){
							$pay_period = $pay_period - $roundMod + abs($modFactor);
						}
					}else if($modFactor < 0){
						if($roundMod > 0){
							$pay_period = $pay_period - $roundMod;
						}
					}*/
				}
			}
			
			for($i = 1;$i <= $period;$i++){
				$arrPaymentPerPeriod = array();
				if($calint_type === "1"){ // 
					if($i == 1){
						if($cal_start_pay_date == "next"){
							$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
						}else{
							$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						}
						$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
						$dayOfMonth++;
					}else {
						$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
						$dayOfMonth = date('d',strtotime($lastDate));
					}
					$dayinYear = 365;
					if($int_rate == 0){
						$period_int = 0;
					}else{
						$period_int = round($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear,2);
					}
					if (($payment_sumbalance) < $pay_period) {
						$prn_amount = $payment_sumbalance;
					}else{
						$prn_amount = $pay_period;
					}
					$periodPayment = $prn_amount + $period_int;
					$payment_sumbalance = round($payment_sumbalance - $prn_amount,2);
					$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'D m Y');
					$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
					$arrPaymentPerPeriod["DAYS"] = $dayOfMonth;
					$arrPaymentPerPeriod["PERIOD"] = $i;
					$arrPaymentPerPeriod["INTEREST"] = number_format($period_int,2);
					$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($periodPayment,2);
					$arrPaymentPerPeriod["PRINCIPAL_BALANCE"] = number_format($payment_sumbalance,2);
					
				}else if($calint_type === "2"){ // ʹ  + ͡ ҡѹء͹
					if($i == 1){
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
						$dayOfMonth++;
					}else {
						$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
						$dayOfMonth = date('d',strtotime($lastDate));
					}
					$dayinYear = 365;
					if($int_rate == 0){
						$period_int = 0;
					}else{
						$period_int = round($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear,2);
					}
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
							$periodPayment = $periodPaymentRaw;
						}
					}
					$payment_sumbalance = round($payment_sumbalance - ($prn_amount),2);
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
		}else{
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
							$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
							$dayOfMonth++;
						}else {
							$lastDate = date('Y-m-t',strtotime("+".($period)." months",$lastDateofMonth));
							$dayOfMonth = date('d',strtotime($lastDate));
						}

						if($int_rate == 0){
							$intPerPeroid = 0;
						}else{
							$intPerPeroid = ($amt * ($int_rate) * $dayOfMonth) / $dayinYear;
						}

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
					if($int_rate == 0){
						$pay_period = $payment_sumbalance / $period;
					}else{
						$payment_per_period = exp(($period * (-1)) * log(((1 + ($int_rate / 12)))));
						$pay_period = ($payment_sumbalance * ($int_rate / 12) / (1 - ($payment_per_period)));
					}
					/*$modFactor = $rowPayRound["PAYROUND_FACTOR"] ?? 5;
					$roundMod = fmod($pay_period,abs($modFactor));
					if($modFactor > 0){
						if($roundMod > 0){
							$pay_period = $pay_period - $roundMod + abs($modFactor);
						}
					}else if($modFactor < 0){
						if($roundMod > 0){
							$pay_period = $pay_period - $roundMod;
						}
					}*/
				}
			}
			$oddeven = true;
			for($i = 1;$i <= $period;$i++){
				$arrPaymentPerPeriod = array();
				if($calint_type === "1"){ // 
					if($i == 1){
						if($cal_start_pay_date == "next"){
							$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date)));
						}else{
							$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						}
						$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
						
					}else {
						if($oddeven){
							$oddeven = false;
							$lastDate = date('Y-m-d',strtotime("+".(($i-1) * 15)." days",$lastDateofMonth));
						}else{
							$oddeven = true;
							$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
						}
						$dayOfMonth = date('d',strtotime($lastDate));

					}
					$dayinYear = 365;
					if($int_rate == 0){
						$period_int = 0;
					}else{
						$period_int = round($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear,2);
					}
					if (($payment_sumbalance) < $pay_period) {
						$prn_amount = $payment_sumbalance;
					}else{
						$prn_amount = $pay_period;
					}
					$periodPayment = $prn_amount + $period_int;
					$payment_sumbalance = round($payment_sumbalance - $prn_amount,2);
					$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'D m Y');
					$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
					$arrPaymentPerPeriod["DAYS"] = $dayOfMonth;
					$arrPaymentPerPeriod["PERIOD"] = $i;
					$arrPaymentPerPeriod["INTEREST"] = number_format($period_int,2);
					$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($periodPayment,2);
					$arrPaymentPerPeriod["PRINCIPAL_BALANCE"] = number_format($payment_sumbalance,2);
					
				}else if($calint_type === "2"){ // ʹ  + ͡ ҡѹء͹
					if($i == 1){
						$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
						$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
			
					}else {
						if($i % 2 == 0){
							$lastDate = date('Y-m',strtotime("+".($j-1)." months",$lastDateofMonth)).'-15';
							$j++;
						}else{
							$lastDate = date('Y-m-t',strtotime("+".($j-2)." months",$lastDateofMonth));
						}
						if($i % 2 == 0){
							$dayOfMonth = date('d',strtotime($lastDate));
						}else{
							$dayOfMonth = date('d',strtotime($lastDate)) - 15;
						}
					}
					$dayinYear = 365;
					if($int_rate == 0){
						$period_int = 0;
					}else{
						$period_int = round($payment_sumbalance * $int_rate * $dayOfMonth / $dayinYear,2);
					}
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
							$periodPayment = $periodPaymentRaw;
						}
					}
					$payment_sumbalance = round($payment_sumbalance - ($prn_amount),2);
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