<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','repayloanapprove')){
		$arrAllLoan = array();
		$fetchRepayLoan = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
											ln.loanapprove_amt as APPROVE_AMT,ln.startcont_date,ln.period_payment,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.loancontract_no = :loancontract_no and ln.contract_status > 0 and ln.contract_status <> 8");
		$fetchRepayLoan->execute([':loancontract_no' => $dataComing["loancontract_no"]]);
		while($rowRepayloan = $fetchRepayLoan->fetch(PDO::FETCH_ASSOC)){
			$arrContract = array();
			$arrContract["LOANCONTRACT_NO"] = $dataComing["loancontract_no"];
			$arrContract["LOAN_BALANCE"] = number_format($rowRepayloan["LOAN_BALANCE"],2);
			$arrContract["APPROVE_AMT"] = number_format($rowRepayloan["APPROVE_AMT"],2);
			$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowRepayloan["LAST_OPERATE_DATE"],'y-n-d');
			$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowRepayloan["LAST_OPERATE_DATE"],'D m Y');
			$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowRepayloan["STARTCONT_DATE"],'D m Y');
			$arrContract["PERIOD_PAYMENT"] = number_format($rowRepayloan["PERIOD_PAYMENT"],2);
			$arrContract["PERIOD"] = $rowRepayloan["LAST_PERIOD"].' / '.$rowRepayloan["PERIOD"];
			$arrAllLoan = $arrContract;
		}
		
		$arrayResult["DETAIL_LOAN"] = $arrAllLoan;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>