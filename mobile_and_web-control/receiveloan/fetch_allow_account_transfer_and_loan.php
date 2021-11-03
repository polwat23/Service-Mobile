<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrLoanGrp = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_receive_loan = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
				$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
			}
			$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.withdrawable_amt as prncbal,dpm.depttype_code
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
			$fetchLoanRepay = $conoracle->prepare("SELECT ln.LOANCONTRACT_NO,LT.LOANTYPE_CODE,LT.LOANTYPE_DESC,LN.PRINCIPAL_BALANCE,LN.WITHDRAWABLE_AMT
													FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE
													WHERE ln.loantype_code IN(".implode(',',$arrGrpLoan).") and ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
			$fetchLoanRepay->execute([':member_no' => $member_no]);
			while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
				$arrLoan = array();
				$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
				$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
				$arrLoan["PRN_BALANCE"] = number_format($rowLoan["WITHDRAWABLE_AMT"],2);
				$arrLoan["BALANCE"] = $rowLoan["PRINCIPAL_BALANCE"];
				$arrLoan["BALANCE_FORMAT"] = number_format($rowLoan["PRINCIPAL_BALANCE"],2);
				$arrLoanGrp[] = $arrLoan;
			}
			if(sizeof($arrGroupAccAllow) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['LOAN'] = $arrLoanGrp;
				$arrayResult['IS_FEE_INFO'] = TRUE;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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