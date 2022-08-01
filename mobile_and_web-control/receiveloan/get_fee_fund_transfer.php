<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		if(isset($dataComing["deptaccount_no"]) && $dataComing["deptaccount_no"] != ""){
			$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
			$fetchLoanRepay = $conmssql->prepare("SELECT LOANCONTRACT_NO,PRINCIPAL_BALANCE,WITHDRAWABLE_AMT
													FROM lncontmaster
													WHERE loancontract_no = :contract_no and contract_status > 0 and contract_status <> 8");
			$fetchLoanRepay->execute([':contract_no' => $contract_no]);
			$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
			if($dataComing["amt_transfer"] > $rowLoan["WITHDRAWABLE_AMT"]){
				$arrayResult["RESPONSE_CODE"] = 'WS0093';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}else{
				$interest = $cal_loan->calculateIntArrAPI($contract_no,$dataComing["amt_transfer"]);
				$arrOther = array();
				if($interest["INT_ARREAR"] > 0){
					$arrOther["LABEL"] = 'ดอกเบี้ย';
					$arrOther["VALUE"] = number_format($interest["INT_PERIOD"],2)." บาท";
					$arrayResult["OTHER_INFO"][] = $arrOther;
				}
				$arrOther["LABEL"] = 'หนี้คงเหลือหลังทำรายการ';
				$arrOther["VALUE"] = number_format($rowLoan["PRINCIPAL_BALANCE"] + $dataComing["amt_transfer"],2)." บาท";
				$arrayResult["OTHER_INFO"][] = $arrOther;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			$fetchLoanRepay = $conmssql->prepare("SELECT LOANCONTRACT_NO,PRINCIPAL_BALANCE,WITHDRAWABLE_AMT
													FROM lncontmaster
													WHERE loancontract_no = :contract_no and contract_status > 0 and contract_status <> 8");
			$fetchLoanRepay->execute([':contract_no' => $contract_no]);
			$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
			if($dataComing["amt_transfer"] > $rowLoan["WITHDRAWABLE_AMT"]){
				$arrayResult["RESPONSE_CODE"] = 'WS0093';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}else{
				$interest = $cal_loan->calculateIntArrAPI($contract_no,$dataComing["amt_transfer"]);
				$arrOther = array();
				if($interest["INT_ARREAR"] > 0){
					$arrOther["LABEL"] = 'ดอกเบี้ย';
					$arrOther["VALUE"] = number_format($interest["INT_PERIOD"],2)." บาท";
					$arrayResult["OTHER_INFO"][] = $arrOther;
				}
				$fetchDataDeposit = $conmysql->prepare("SELECT gba.citizen_id,gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.itemtype_dep,csb.fee_withdraw,
														csb.link_withdraw_coopdirect,csb.bank_short_ename,gba.account_payfee
														FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
														WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
				$fetchDataDeposit->execute([':member_no' => $payload["member_no"]]);
				$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
				$getTransactionForFee = $conmysql->prepare("SELECT COUNT(ref_no) as C_TRANS FROM gctransaction WHERE member_no = :member_no and trans_flag = '-1' and
															transfer_mode = '9' and result_transaction = '1' and DATE_FORMAT(operate_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')");
				$getTransactionForFee->execute([
					':member_no' => $payload["member_no"]
				]);
				$rowCountFee = $getTransactionForFee->fetch(PDO::FETCH_ASSOC);
				if($rowCountFee["C_TRANS"] + 1 > 2){
					$fee_amt = $rowDataWithdraw["fee_withdraw"];
				}else{
					$fee_amt = 0;
				}
				if($fee_amt > 0){
					$getBalanceAccFee = $conmssql->prepare("SELECT PRNCBAL FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
					$getBalanceAccFee->execute([':deptaccount_no' => $rowDataWithdraw["account_payfee"]]);
					$rowBalFee = $getBalanceAccFee->fetch(PDO::FETCH_ASSOC);
					$dataAccFee = $cal_dep->getConstantAcc($rowDataWithdraw["account_payfee"]);
					if($rowBalFee["PRNCBAL"] - $fee_amt < $dataAccFee["MINPRNCBAL"]){
						$arrayResult['RESPONSE_CODE'] = "WS0100";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
					$arrOther["LABEL"] = 'ค่าธรรมเนียม';
					$arrOther["VALUE"] = number_format($fee_amt,2)." บาท";
					$arrayResult["OTHER_INFO"][] = $arrOther;
				}
				$arrOther["LABEL"] = 'หนี้คงเหลือหลังทำรายการ';
				$arrOther["VALUE"] = number_format($rowLoan["PRINCIPAL_BALANCE"] + $dataComing["amt_transfer"],2)." บาท";
				$arrayResult["OTHER_INFO"][] = $arrOther;
				$arrayResult['RESULT'] = TRUE;
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