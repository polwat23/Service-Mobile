<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.citizen_id,gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.itemtype_dep,csb.fee_withdraw,
												csb.link_withdraw_coopdirect,csb.bank_short_ename,gba.account_payfee
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
		$fetchDataDeposit->execute([':member_no' => $member_no]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
		$deptaccount_no = preg_replace('/-/','',$rowDataWithdraw["deptaccount_no_bank"]);
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
		$itemtypeDeposit = 'DTL';
		$dataCont = $cal_loan->getContstantLoanContract($contract_no);
		
		if($dataCont["WITHDRAWABLE_AMT"] == 0 && $dataCont["PRINCIPAL_BALANCE"] == 0){
			$dataCont["WITHDRAWABLE_AMT"] = $dataCont["LOANAPPROVE_AMT"];
		}
		if($dataComing["amt_transfer"] > $dataCont["WITHDRAWABLE_AMT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0093';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$insertReceiveloanOd = $conmysql->prepare("INSERT INTO gcreceiveloanod(member_no, loancontract_no, amount_receive, withdrawable_amt,principal_balance,deptaccount_no,is_bank, calint_from, calint_to,id_userlogin,payload)
													VALUES(:member_no, :contract_no ,:amount_receive,:withdrawable_amt,:principal_balance,:deptaccount_no,'1',:calint_from, NOW(),:id_userlogin ,:payload)");
		if($insertReceiveloanOd->execute([
			':member_no' => $member_no,
			':contract_no' => $contract_no,
			':amount_receive' => $dataComing["amt_transfer"],
			':withdrawable_amt' => $dataCont["WITHDRAWABLE_AMT"],
			':principal_balance' => $dataCont["PRINCIPAL_BALANCE"],
			':deptaccount_no' => $deptaccount_no,
			':calint_from' =>  date('Y-m-d',strtotime($dataCont["LASTCALINT_DATE"])),
			':id_userlogin' => $payload["id_userlogin"],
			':payload' => json_encode($payload ,JSON_UNESCAPED_UNICODE )
			
		])){
		
		}else{
			$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		
		$logStruc = [
			":member_no" => $payload["member_no"],
			":request_amt" => $dataComing["amt_transfer"],
			":deptaccount_no" => $deptaccount_no,
			":loancontract_no" => $contract_no,
			":status_flag" => '1',
			':id_userlogin' => $payload["id_userlogin"]
		];
		$log->writeLog('receiveloan',$logStruc);
		$arrayResult['TRANSACTION_NO'] = $ref_no;
		$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
		$arrayResult['RESULT'] = TRUE;
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