<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrayDept = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_payloan = '1'");
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
				if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDataAccAllow["DEPTTYPE_CODE"].'.png')){
					$arrAccAllow["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDataAccAllow["DEPTTYPE_CODE"].'.png?v='.date('Ym');
				}else{
					$arrAccAllow["DEPT_TYPE_IMG"] = null;
				}
				$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
				$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
				$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
				$arrAccAllow["BALANCE"] = $rowDataAccAllow["PRNCBAL"];
				$arrAccAllow["BALANCE_FORMAT"] = number_format($rowDataAccAllow["PRNCBAL"],2);
				$arrGroupAccAllow[] = $arrAccAllow;
			}
			$getLoanConstant = $conoracle->prepare("SELECT RDINTDEC_TYPE,RDINTSATANG_TYPE,DAYINYEAR FROM lnloanconstant");
			$getLoanConstant->execute();
			$rowLoanCont = $getLoanConstant->fetch(PDO::FETCH_ASSOC);
			$fetchLoanRepay = $conoracle->prepare("SELECT lnt.loantype_desc,lnm.loancontract_no,lnm.principal_balance,lnm.period_payamt,lnm.last_periodpay,lnm.LOANTYPE_CODE,
													lnm.LASTCALINT_DATE,lnm.LOANPAYMENT_TYPE,lnd.INTEREST_RATE,
													(CASE WHEN lnm.lastprocess_date <= lnm.LASTCALINT_DATE OR lnm.lastprocess_date IS NULL THEN '1' ELSE '0' END) as CHECK_KEEPING
													FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
													LEFT JOIN lncfloanintratedet lnd 
													ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
													WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8 
													and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE");
			$fetchLoanRepay->execute([':member_no' => $member_no]);
			while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
				$interest = 0;
				$arrLoan = array();
				if($rowLoan["CHECK_KEEPING"] == '1'){
					if($rowLoan["LOANPAYMENT_TYPE"] == '1'){
						$daydiff = $lib->count_duration($rowLoan["LASTCALINT_DATE"],'d');
						$interest = (($rowLoan["PRINCIPAL_BALANCE"] * ($rowLoan["INTEREST_RATE"] / 100)) * $daydiff) / $rowLoanCont["DAYINYEAR"];
						$interest = $lib->roundDecimal($interest,$rowLoanCont["RDINTSATANG_TYPE"]);
					}else{
						$daydiff = $lib->count_duration($rowLoan["LASTCALINT_DATE"],'d');
						$interest = (($rowLoan["PRINCIPAL_BALANCE"] * ($rowLoan["INTEREST_RATE"] / 100)) * $daydiff) / $rowLoanCont["DAYINYEAR"];
						$interest = $lib->roundDecimal($interest,$rowLoanCont["RDINTSATANG_TYPE"]);
					}
					$arrLoan["INT_BALANCE"] = number_format($interest,2);
				}
				
				if(file_exists(__DIR__.'/../../resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png')){
					$arrLoan["LOAN_TYPE_IMG"] = $config["URL_SERVICE"].'resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png?v='.date('Ym');
				}else{
					$arrLoan["LOAN_TYPE_IMG"] = null;
				}
				$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
				$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
				$arrLoan["BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"],2);
				$arrLoan["PERIOD_ALL"] = number_format($rowLoan["PERIOD_PAYAMT"],0);
				$arrLoan["PERIOD_BALANCE"] = number_format($rowLoan["LAST_PERIODPAY"],0);
				$arrLoanGrp[] = $arrLoan;
			}
			if(sizeof($arrGroupAccAllow) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['LOAN'] = $arrLoanGrp;
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>