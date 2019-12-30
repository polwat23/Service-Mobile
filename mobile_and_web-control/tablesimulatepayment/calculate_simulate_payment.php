<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','int_rate','payment_sumbalance','calint_type','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentSimulateTable')){
		$request_date = $dataComing['request_date'];
		$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
		$pay_date = date("Y-m-t", strtotime('last day of '.$cal_start_pay_date.' month',strtotime($lib->convertdate($request_date,'y-N-d'))));
		$dayinYear = $lib->getnumberofYear(date('Y',strtotime($pay_date)));
		$payment_sumbalance = (float) preg_replace('/,/','',$dataComing['payment_sumbalance']);
		if($lib->checkCompleteArgument(['period'],$dataComing)){
			$period = $dataComing["period"];
		}else{
			
		}
		$int_rate = $dataComing["int_rate"]/100;
		$calint_type = $dataComing["calint_type"];
		$arrPayment = array();
		$lastDateofMonth = strtotime(date('M Y',strtotime($pay_date)));
		$payment_per_period = 0;
		for($i = 1;$i <= $period;$i++){
			$arrPaymentPerPeriod = array();
			if($calint_type === "2"){ // คงยอด ต้น + ดอก เท่ากันทุกเดือน
				if($i == 1){
					$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($pay_date,'d m Y');
					if(date('m',strtotime($pay_date) == 12)){
						$arrPaymentPerPeriod["END_YEAR"] = TRUE;
					}else{
						$arrPaymentPerPeriod["END_YEAR"] = FALSE;
					}
					$dayOfMonth = date('d',strtotime($pay_date)) + (date("t",strtotime($request_date)) - date("d",strtotime($request_date))) + 1;
				}else{
					$lastDate = date('Y-m-t',strtotime("+".($i-1)." months",$lastDateofMonth));
					$arrPaymentPerPeriod["MUST_PAY_DATE"] = $lib->convertdate($lastDate,'d m Y');
					if(date('m',strtotime($pay_date) == 12)){
						$arrPaymentPerPeriod["END_YEAR"] = TRUE;
					}else{
						$arrPaymentPerPeriod["END_YEAR"] = FALSE;
					}
					$dayOfMonth = date('d',strtotime($lastDate));
				}
				$interest = (($payment_sumbalance * $int_rate) * $dayOfMonth)/$dayinYear;
				$payment_per_period += $interest;
				$prn_amount = $payment_per_period - $interest;
				$payment_sumbalance -= $prn_amount;
				$arrPaymentPerPeriod["PRN_AMOUNT"] = number_format($prn_amount,2);
				$arrPaymentPerPeriod["PERIOD"] = $i;
				$arrPaymentPerPeriod["INTEREST"] = number_format($interest,2);
				$arrPaymentPerPeriod["PAYMENT_PER_PERIOD"] = number_format($payment_per_period,2);
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
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>