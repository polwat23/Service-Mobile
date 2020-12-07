<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrpDeptAll = array();
		$arrGrpAllDept = array();
		$arrGrpAllLoan = array();
		$arrGrpLoanAll = array();
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			foreach($arrResponseAPI->accountDetail as $accData){
				if ($accData->accountStatus == "0"){
					$arraysumDept = array();
					$arrGrpDept = array();
					$arrGrpAllDept["SUM_ALL_ACC"] += preg_replace('/,/', '', $accData->accountBalance);
					$arrGrpAllDept["COUNT_ACC"]++;
					$arraysumDept["BALANCE"] = preg_replace('/,/', '', $accData->accountBalance);
					$arraysumDept["ACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
					$arraysumDept["SOURCE_NO"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
					$arraysumDept["TYPE_DESC"] = $accData->accountDesc;
					$arrGrpDept['TYPE_ACCOUNT'] = $accData->accountDesc;
					if(array_search($accData->accountDesc,array_column($arrGrpDeptAll,'TYPE_ACCOUNT')) === False){
						($arrGrpDept['ACCOUNT'])[] = $arraysumDept;
						$arrGrpDept['SUM_BAL_IN_TYPE'] += preg_replace('/,/', '', $accData->accountBalance);
						$arrGrpDeptAll[] = $arrGrpDept;
					}else{
						($arrGrpDeptAll[array_search($accData->accountDesc,array_column($arrGrpDeptAll,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arraysumDept;
						($arrGrpDeptAll[array_search($accData->accountDesc,array_column($arrGrpDeptAll,'TYPE_ACCOUNT'))])["SUM_BAL_IN_TYPE"] += preg_replace('/,/', '', $accData->accountBalance);
					}
				}
			}
		}
		$getSumAllLoan = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as BALANCE
															FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
															WHERE ln.member_no = :member_no and ln.contract_status > 0");
		$getSumAllLoan->execute([':member_no' => $member_no]);
		while($rowSumAllLoan = $getSumAllLoan->fetch(PDO::FETCH_ASSOC)){
			$arraysumLoan = array();
			$arrGrpLoan = array();
			$arrGrpAllLoan["SUM_ALL_ACC"] += $rowSumAllLoan["BALANCE"];
			$arrGrpAllLoan["COUNT_ACC"]++;
			$arraysumLoan["BALANCE"] = $rowSumAllLoan["BALANCE"];
			$arraysumLoan["SOURCE_NO"] = preg_replace('/\//','',$rowSumAllLoan["LOANCONTRACT_NO"]);
			$arraysumLoan["TYPE_ACCOUNT"] = $rowSumAllLoan["LOAN_TYPE"];
			$arrGrpLoan['TYPE_ACCOUNT'] = $rowSumAllLoan["LOAN_TYPE"];
			if(array_search($rowSumAllLoan["LOAN_TYPE"],array_column($arrGrpLoanAll,'TYPE_ACCOUNT')) === False){
				($arrGrpLoan['ACCOUNT'])[] = $arraysumLoan;
				$arrGrpLoan['SUM_BAL_IN_TYPE'] += $rowSumAllLoan["BALANCE"];
				$arrGrpLoanAll[] = $arrGrpLoan;
			}else{
				($arrGrpLoanAll[array_search($rowSumAllLoan["LOAN_TYPE"],array_column($arrGrpLoanAll,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arraysumLoan;
				($arrGrpLoanAll[array_search($rowSumAllLoan["LOAN_TYPE"],array_column($arrGrpLoanAll,'TYPE_ACCOUNT'))])["SUM_BAL_IN_TYPE"] += $rowSumAllLoan["BALANCE"];
			}
		}
		$getSumAllLoanShare = $conoracle->prepare("SELECT (sharestk_amt * 10) as SUM_SHARE FROM shsharemaster WHERE member_no = :member_no");
		$getSumAllLoanShare->execute([':member_no' => $member_no]);
		$rowSumAllShareBal = $getSumAllLoanShare->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SHARE_BALANCE'] = $rowSumAllShareBal["SUM_SHARE"];
		$arrGrpAllDept['INFO'] = $arrGrpDeptAll;
		$arrGrpAllLoan["INFO"] = $arrGrpLoanAll;
		$arrayResult['COLLECT_DEPT'] = $arrGrpAllDept;
		$arrayResult['COLLECT_LOAN'] = $arrGrpAllLoan;
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