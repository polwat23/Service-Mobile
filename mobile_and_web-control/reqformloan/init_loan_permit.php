<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loantype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != "" && isset($dataComing["period"]) && $dataComing["period"] != ""){
			$period_payment = $dataComing["request_amt"] / $dataComing["period"];
			$arrayResult["PERIOD"] = $dataComing["period"];
			$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt,lc.percentshare,lc.percentsalary,mb.salary_amount,(sh.sharestk_amt*10) as SHARE_AMT
														FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb 
														LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
														WHERE mb.member_no = :member_no and LT.LOANTYPE_CODE = :loantype_code
														and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
			$fetchCredit->execute([
				':member_no' => $member_no,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
			$maxloan_amt = 0;
			$salaryPercent = $rowCredit["SALARY_AMOUNT"] * $rowCredit["PERCENTSALARY"];
			$sharePercent = $rowCredit["SHARE_AMT"] * $rowCredit["PERCENTSHARE"];
			if($salaryPercent > $sharePercent){
				$maxloan_amt = $salaryPercent;
			}else{
				$maxloan_amt = $sharePercent;
			}
			if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
				$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
			}
			$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD 
															FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
															WHERE :request_amt >= lnd.MONEY_FROM and :request_amt < lnd.MONEY_TO and lnd.LOANTYPE_CODE = :loantype_code");
			$getMaxPeriod->execute([
				':request_amt' => $maxloan_amt,
				':loantype_code' => $dataComing["loantype_code"]
			]);
			$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
			$period_payment = $maxloan_amt / $rowMaxPeriod["MAX_PERIOD"];
			
			$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
			$arrayResult["REQUEST_AMT"] = $maxloan_amt;
			//$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $request_amt;
			$arrayResult["MAX_PERIOD"] = 12;
			$arrayResult["PERIOD_PAYMENT"] = $period_payment;
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