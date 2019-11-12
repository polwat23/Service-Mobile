<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) 
&& $lib->checkCompleteArgument(['menu_component','int_rate','period','payment_sumbalance','payment_per_period','calint_type','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'PaymentSimulateTable')){
		$request_date = $dataComing['request_date'];
		$pay_date = date("Y-m-t", strtotime($lib->convertdate($request_date,'y-N-d')));
		$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
		$payment_per_period = (float) preg_replace('/,/','',$dataComing['payment_per_period']);
		$payment_sumbalance = (float) preg_replace('/,/','',$dataComing['payment_sumbalance']);
		$period = $dataComing["period"];
		$int_rate = $dataComing["int_rate"];
		$calint_type = $dataComing["calint_type"];
		$arrPayment = array();
		$lastDateofMonth = strtotime(date('M Y',strtotime($pay_date)));
		for($i = 1;$i <= $period;$i++){
			$arrPaymentPerPeriod = array();
			if($calint_type === "1"){ // คงยอด ต้น + ดอก เท่ากันทุกเดือน
				$arrPaymentPerPeriod["LOAN_BALANCE"] = number_format($payment_sumbalance,2);
				$arrPaymentPerPeriod["PRN_BALANCE"] = number_format($payment_per_period,2);
				if($i == 1){
					$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($pay_date,'d m Y');
					$dayOfMonth = date('d',strtotime($pay_date)) - date("d",strtotime($request_date));
				}else{
					$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
					$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'d m Y');
					$dayOfMonth = date('d',strtotime($lastDate)) - date("d",strtotime($request_date));
				}
				$interest = (($payment_sumbalance * ($int_rate/100)) * $dayOfMonth)/$dayinYear;
				$payment_per_period = ($payment_per_period - $interest) + $interest;
				$payment_sumbalance -= $payment_per_period;
				$arrPaymentPerPeriod["PERIOD"] = $i;
				$arrPaymentPerPeriod["DAY_ON_MONTH"] = $dayOfMonth;
				$arrPaymentPerPeriod["INTEREST"] = number_format($interest,2);
				$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($payment_per_period,2);
				$arrPaymentPerPeriod["PAYMENT_BALANCE"] = number_format($payment_sumbalance,2);
			}
			$arrPayment[] = $arrPaymentPerPeriod;
		}
		echo json_encode($arrPayment);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>