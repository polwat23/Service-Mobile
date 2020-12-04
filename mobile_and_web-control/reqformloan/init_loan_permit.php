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
			$receive_net = $dataComing["request_amt"] - $oldBal;
			if($receive_net < 0){
				$arrayResult['RESPONSE_CODE'] = "WS0086";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				$arrayResult["RECEIVE_NET"] = $receive_net;
			}
			$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $dataComing["request_amt"];
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
				$request_amt = $oldBal;
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
			$arrayResult["DIFFOLD_CONTRACT"] = $oldBal;
			$arrayResult["RECEIVE_NET"] = $receive_net;
			$arrayResult["REQUEST_AMT"] = $request_amt;
			$arrayResult["LOAN_PERMIT_BALANCE"] = $maxloan_amt - $request_amt;
			$arrayResult["LOAN_PERMIT_AMT"] = $maxloan_amt;
			$arrayResult["MAX_PERIOD"] = (string)$dayremainEnd;
			$arrayResult["DISABLE_PERIOD"] = TRUE;
			if($dataComing["loantype_code"] != '23'){
				$arrayResult["PERIOD_PAYMENT"] = $period_payment;
			}
			$arrayResult["TERMS_HTML"]["uri"] = "https://policy.gensoft.co.th/".((explode('-',$config["COOP_KEY"]))[0] ?? $config["COOP_KEY"])."/termanduse.html";
			$arrayResult["SPEC_REMARK"] =  $configError["SPEC_REMARK"][0][$lang_locale];
			$arrayResult["REQ_SALARY"] = FALSE;
			$arrayResult["REQ_CITIZEN"] = FALSE;
			$arrayResult["REQ_BANK_ACCOUNT"] = TRUE;
			$arrayResult["IS_UPLOAD_CITIZEN"] = FALSE;
			$arrayResult["IS_UPLOAD_SALARY"] = FALSE;
			$arrayResult["IS_BANK_ACCOUNT"] = TRUE;
			$arrayResult["BANK_ACCOUNT_REMARK"] = null;
			if($dayremainEnd == 0){
				$arrayResult["NOTE_DESC"] = "เงินต้นและดอกเบี้ยของท่าน ณ วันที่กู้จะถูกหักรวมกับยอดปันผล-เฉลี่ยคืนที่ท่านจะได้รับ";
				$arrayResult["NOTE_DESC_COLOR"] = "red";
			}
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