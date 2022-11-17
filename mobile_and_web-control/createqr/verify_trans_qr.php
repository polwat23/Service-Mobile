<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','trans_code','trans_amount'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		if ($dataComing["trans_mode"] == 'bank') {
			$fetchBindAccount = $conmysql->prepare("SELECT id_bindaccount 
												FROM gcbindaccount
												WHERE member_no = :member_no and bindaccount_status = '1' and bank_code = '999' ORDER BY deptaccount_no_coop");
			$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
			if($fetchBindAccount->rowCount() == 0){
				$arrayResult['RESPONSE_CODE'] = "WS0021";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		
		if($dataComing["trans_code"] == '01'){
			$deptaccount_no = preg_replace('/-/','',$dataComing["destination"]);
			$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$dataComing["trans_amount"],"TransactionDeposit","999",false);
			if($arrRightDep["RESULT"]){
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
				if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["trans_code"] == '02'){
			$fetchLoanRepay = $conoracle->prepare("SELECT LCONT_AMOUNT_SAL as PRINCIPAL_BALANCE
													FROM LOAN_M_CONTACT
													WHERE LCONT_ID = :loancontract_no");
			$fetchLoanRepay->execute([':loancontract_no' => $dataComing["destination"]]);
			$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
			$interest = $cal_loan->calculateIntAPI($dataComing["destination"],$dataComing["trans_amount"]);
			$amt_prin = $dataComing["trans_amount"] - $interest["INT_PAYMENT"];
			if($dataComing["trans_amount"] > ($rowLoan["PRINCIPAL_BALANCE"]) + $interest["INT_PAYMENT"]){
				$arrayResult['RESPONSE_CODE'] = "WS0098";
				$arrayResult['dataConst'] = $rowLoan;
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["trans_code"] == '03'){
			$getCurrShare = $conoracle->prepare("SELECT SHR_SUM_BTH FROM SHR_MEM WHERE account_id = :member_no");
			$getCurrShare->execute([':member_no' => $dataComing["destination"]]);
			$rowCurrShare = $getCurrShare->fetch(PDO::FETCH_ASSOC);
			$sharereq_value = $rowCurrShare["SHARESTK_AMT"] + $dataComing["trans_amount"];
			$shareround_factor = 100;
			if($sharereq_value < $shareround_factor){
				$arrayResult['RESPONSE_CODE'] = "WS0075";
				if(isset($configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${SHAREROUND_FACTOR}',number_format($shareround_factor,2),$configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($dataComing["trans_amount"] % $shareround_factor > 0){
				if(isset($configError["BUY_SHARES_ERR"][0]["0004"][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${SHAREROUND_FACTOR}',number_format($shareround_factor,2),$configError["BUY_SHARES_ERR"][0]["0004"][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			
		}
		$arrayResult["RESULT"] = TRUE;
		require_once('../../include/exit_footer.php');
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