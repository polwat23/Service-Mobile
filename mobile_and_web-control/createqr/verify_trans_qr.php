<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','trans_code','trans_amount','destination'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($dataComing["trans_code"] == '01'){
			$deptaccount_no = preg_replace('/-/','',$dataComing["destination"]);
			$checkSeqAmt = $cal_dep->getSequestAmt($deptaccount_no,'DTE');
			if($checkSeqAmt["CAN_DEPOSIT"]){
				$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$dataComing["trans_amount"],"TransactionDeposit","999");
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
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0104";
				$arrayResult['RESPONSE_MESSAGE'] = $checkSeqAmt["SEQUEST_DESC"];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["trans_code"] == '02'){
			$fetchLoanRepay = $conoracle->prepare("SELECT PRINCIPAL_BALANCE,INTEREST_RETURN,RKEEP_PRINCIPAL,LAST_PERIODPAY,
													LOANTYPE_CODE
													FROM lncontmaster
													WHERE loancontract_no = :loancontract_no");
			$fetchLoanRepay->execute([':loancontract_no' => $dataComing["destination"]]);
			$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
			$interest = $cal_loan->calculateInterest($dataComing["destination"],$dataComing["trans_amount"]);
			$amt_prin = $dataComing["trans_amount"] - $interest;
			if($dataComing["trans_amount"] > ($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest){
				$arrayResult['RESPONSE_CODE'] = "WS0098";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			if(($rowLoan["LOANTYPE_CODE"] == '12' || $rowLoan["LOANTYPE_CODE"] == '30') && $rowLoan["LAST_PERIODPAY"] < 24){
				$getMemberType = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
				$getMemberType->execute([':member_no' => $member_no]);
				$rowMembType = $getMemberType->fetch(PDO::FETCH_ASSOC);
				if(TRIM($rowMembType["MEMBGROUP_CODE"]) != '0110'){
					$fee_amt = $amt_prin * 0.02;
					$arrOther = array();
					$arrOther["LABEL"] = 'ค่าธรรมเนียมชำระก่อนกำหนด';
					$arrOther["VALUE_TEXT_PROPS"] = ['color' => 'red'];
					$arrOther["VALUE"] = number_format($fee_amt,2)." บาท";
					$arrayResult["OTHER_INFO"][] = $arrOther;
				}
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