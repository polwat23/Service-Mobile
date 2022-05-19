<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrLoanGrp = array();
		$arrGroupAccBind = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$getMemberRetryDate = $conoracle->prepare("SELECT RETRY_DATE FROM mbmembmaster WHERE member_no = :member_no");
		$getMemberRetryDate->execute([':member_no' => $member_no]);
		$rowMemberRetryDate = $getMemberRetryDate->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMemberRetryDate["RETRY_DATE"]) && $rowMemberRetryDate["RETRY_DATE"] != ""){
			$dateRetry = new DateTime(date('d-m-Y',strtotime($rowMemberRetryDate["RETRY_DATE"])));
			$dateNow = new DateTime(date('d-m-Y'));
			$date_duration = $dateRetry->diff($dateNow);
			if($date_duration->days <= 365 && $member_no != "00000218" && $member_no != "00000499"){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["RECEIVE_LOAN_RETRY_BEFORE"][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		$arrayAcc = [];
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_receive_loan = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
			$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
		}
		$getDataBalAcc = $conoracle->prepare("SELECT DPM.DEPTACCOUNT_NO,DPM.DEPTACCOUNT_NAME,DPT.DEPTTYPE_DESC,DPM.WITHDRAWABLE_AMT AS PRNCBAL,DPM.DEPTTYPE_CODE
												FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
												WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).") and dpm.deptclose_status = 0
												ORDER BY dpm.deptaccount_no ASC");
		$getDataBalAcc->execute();
		while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
			$arrAccAllow = array();
			$checkDep = $cal_dep->getSequestAmt($rowDataAccAllow["DEPTACCOUNT_NO"]);
			if($checkDep["CAN_DEPOSIT"]){
				$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$formatDept);
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccAllow["DEPTACCOUNT_NO_FORMAT"],$formatDeptHidden);
				$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
				$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
				$arrAccAllow["BALANCE"] = $cal_dep->getWithdrawable($rowDataAccAllow["DEPTACCOUNT_NO"]) - $checkDep["SEQUEST_AMOUNT"];
				$arrAccAllow["BALANCE_FORMAT"] = number_format($arrAccAllow["BALANCE"],2);
				$arrGroupAccAllow[] = $arrAccAllow;
			}
		}
		$arrGrpLoan = array();
		$fetchAllowReceiveLoantype = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_receive = '1'");
		$fetchAllowReceiveLoantype->execute();
		while($rowLoanConst = $fetchAllowReceiveLoantype->fetch(PDO::FETCH_ASSOC)){
			$arrGrpLoan[] = $rowLoanConst["loantype_code"];
		}
		$fetchLoanRepay = $conoracle->prepare("SELECT LN.LOANCONTRACT_NO,LT.LOANTYPE_CODE,LT.LOANTYPE_DESC,LN.PRINCIPAL_BALANCE,LN.WITHDRAWABLE_AMT,LN.LOANAPPROVE_AMT
												FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE
												WHERE ln.loantype_code IN(".implode(',',$arrGrpLoan).") and ln.member_no = :member_no 
												and ln.contract_status > 0 and ln.contract_status <> 8 and ln.withdrawable_amt > 0 ");
		$fetchLoanRepay->execute([':member_no' => $member_no]);
		while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
			$arrLoan = array();
			$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
			$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
			
			$fetchDataReceive = $conmysql->prepare("SELECT IFNULL(SUM(amount_receive),0) as amount_receive FROM gcreceiveloanod 
													  WHERE loancontract_no = :loancontract_no AND member_no = :member_no AND receive_stauts ='8'");
			$fetchDataReceive->execute([':member_no' => $member_no,
										':loancontract_no' => $rowLoan["LOANCONTRACT_NO"]]);
			$rowDataReceive = $fetchDataReceive->fetch(PDO::FETCH_ASSOC);
			if($rowLoan["WITHDRAWABLE_AMT"] == 0 && $rowLoan["PRINCIPAL_BALANCE"] == 0){
				if($rowLoan["LOANAPPROVE_AMT"] - $rowDataReceive["amount_receive"] < 0){
					$arrLoan["PRN_BALANCE"] ='0.00';
					$arrLoan["BALANCE"] = 0;
				}else{
					$arrLoan["PRN_BALANCE"] = number_format($rowLoan["LOANAPPROVE_AMT"] - $rowDataReceive["amount_receive"],2);
					$arrLoan["BALANCE"] = $rowLoan["LOANAPPROVE_AMT"] - $rowDataReceive["amount_receive"];
				}
			}else{
				if($rowLoan["WITHDRAWABLE_AMT"] - $rowDataReceive["amount_receive"] < 0){
					$arrLoan["PRN_BALANCE"] = '0.00';
					$arrLoan["BALANCE"] = 0;
				}else{
					$arrLoan["PRN_BALANCE"] = number_format($rowLoan["WITHDRAWABLE_AMT"] - $rowDataReceive["amount_receive"],2);
					$arrLoan["BALANCE"] = $rowLoan["WITHDRAWABLE_AMT"] - $rowDataReceive["amount_receive"];
				}        				
			}
			$arrLoan["BALANCE_FORMAT"] = number_format($rowLoan["PRINCIPAL_BALANCE"],2);
			$arrLoan['IS_INIT_AMOUNT'] = TRUE;
			$arrLoan['DISABLE_CHANGE_AMOUNT'] = TRUE;
			$arrLoanGrp[] = $arrLoan;	
		}
		$fetchBindAccount = $conmysql->prepare("SELECT gba.id_bindaccount,gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,gba.bank_code,
												csb.bank_format_account,csb.bank_format_account_hide,csb.bank_short_name
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1' ORDER BY gba.deptaccount_no_coop");
		$fetchBindAccount->execute([':member_no' => $member_no]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC)){
				$arrAccBind = array();
				$arrAccBind["ID_BINDACCOUNT"] = $rowAccBind["id_bindaccount"];
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
				$arrAccBind["BANK_NAME"] = $rowAccBind["bank_short_name"];
				$arrAccBind["BANK_CODE"] = $rowAccBind["bank_code"];
				$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["bank_logo_path"];
				$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
				$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
				if($rowAccBind["bank_code"] == '025'){
					$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $rowAccBind["deptaccount_no_bank"];
				}else{
					$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
				}
				$arrGroupAccBind[] = $arrAccBind;
			}
		}
		if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccBind) > 0){
			$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
			$arrayResult['BANK_ACCOUNT_ALLOW'] = $arrGroupAccBind;
			$arrayResult['LOAN'] = $arrLoanGrp;
			$arrayResult['IS_FEE_INFO'] = TRUE;
			$arrayResult['FIRST_INIT_ACCOUNT'] = "bank";
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
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
