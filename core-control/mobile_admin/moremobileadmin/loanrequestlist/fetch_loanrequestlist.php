<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestlist')){
		$arrayGroup = array();
		
		$fetchLoanReques = $conoracle->prepare("SELECT 
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
			$arrLoanReques["ID_REQLOAN"] = $rowLoanReques["ID_REQLOAN"];
			$arrLoanReques["MEMBER_NO"] = $rowLoanReques["MEMBER_NO"];
			$arrLoanReques["PERIOD_PAYMENT"] = number_format($rowLoanReques["PERIOD_PAYMENT"],2);
			$arrLoanReques["LOANTYPE_CODE"] = $rowLoanReques["LOANTYPE_CODE"];
			$arrLoanReques["REQUEST_DATE"] = $lib->convertdate($rowLoanReques["REQUEST_DATE"],'d m Y',true);
			$arrLoanReques["REQUEST_AMT"] = number_format($rowLoanReques["REQUEST_AMT"],2);
			$arrLoanReques["LOANPERMIT_AMT"] = number_format($rowLoanReques["LOANPERMIT_AMT"],2);
			$arrLoanReques["DEFF_OLD_CONTRACT"] = number_format($rowLoanReques["DIFF_OLD_CONTRACT"],2);
			$arrLoanReques["RECEIVE_NET"] = number_format($rowLoanReques["RECEIVE_NET"],2);
			$arrLoanReques["DEPTACCOUNT_NO"] = $lib->formataccount($rowLoanReques["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
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