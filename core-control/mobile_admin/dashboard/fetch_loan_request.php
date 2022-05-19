<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		
		$fetchSumAll = $conmysql->prepare("SELECT COUNT(reqloan_doc) AS sum_req_loan FROM gcreqloan WHERE req_status  = '8' OR req_status = '7'");
		$fetchSumAll->execute();
		$rowSumAll = $fetchSumAll->fetch(PDO::FETCH_ASSOC);
		
		$fetchLoanReq = $conmysql->prepare("SELECT COUNT(reqloan_doc) AS req_loan FROM gcreqloan WHERE req_status  = '8'");
		$fetchLoanReq->execute();
		$rowLoanReq = $fetchLoanReq->fetch(PDO::FETCH_ASSOC);
		
		$fetchLoanReqChk = $conmysql->prepare("SELECT COUNT(reqloan_doc) AS req_loan_chk FROM gcreqloan WHERE req_status  = '7'");
		$fetchLoanReqChk->execute();
		$rowLoanReqChk = $fetchLoanReqChk->fetch(PDO::FETCH_ASSOC);
			
					
		$arrayResult["SUM_REQ_LOAN"] = $rowSumAll["sum_req_loan"]??0;
		$arrayResult["REQ_LOAN"] = $rowLoanReq["req_loan"]??0;
		$arrayResult["REQ_LOAN_CHK"] = $rowLoanReqChk["req_loan_chk"]??0;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>