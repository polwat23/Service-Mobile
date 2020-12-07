<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestlist')){
		$arrayGroup = array();
		
		$fetchLoanReques = $conmysql->prepare("SELECT 
													id_reqloan,
													member_no,
													loantype_code,
													request_amt,
													deptaccount_no,
													loanpermit_amt,
													period_payment,
													diff_old_contract,
													receive_net,
													request_date
												FROM gcreqloan
												ORDER BY request_date DESC");
		$fetchLoanReques->execute();
		while($rowLoanReques = $fetchLoanReques->fetch()){
			$arrLoanReques = array();
			$arrLoanReques["ID_REQLOAN"] = $rowLoanReques["id_reqloan"];
			$arrLoanReques["MEMBER_NO"] = $rowLoanReques["member_no"];
			$arrLoanReques["PERIOD_PAYMENT"] = number_format($rowLoanReques["period_payment"],2);
			$arrLoanReques["LOANTYPE_CODE"] = $rowLoanReques["loantype_code"];
			$arrLoanReques["REQUEST_DATE"] = $lib->convertdate($rowLoanReques["request_date"],'d m Y',true);
			$arrLoanReques["REQUEST_AMT"] = number_format($rowLoanReques["request_amt"],2);
			$arrLoanReques["LOANPERMIT_AMT"] = number_format($rowLoanReques["loanpermit_amt"],2);
			$arrLoanReques["DEFF_OLD_CONTRACT"] = number_format($rowLoanReques["diff_old_contract"],2);
			$arrLoanReques["RECEIVE_NET"] = number_format($rowLoanReques["receive_net"],2);
			$arrLoanReques["DEPTACCOUNT_NO"] = $lib->formataccount($rowLoanReques["deptaccount_no"],$func->getConstant('dep_format'));
			$arrayGroup[] = $arrLoanReques;
		}
	
		$arrayResult["LOAN_REQUES_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>