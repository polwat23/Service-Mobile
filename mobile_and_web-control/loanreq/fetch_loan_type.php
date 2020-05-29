<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$arrayGrpLoan = array();
		$getLoantype = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_loanrequest = '1'");
		$getLoantype->execute();
		while($rowLoantype = $getLoantype->fetch(PDO::FETCH_ASSOC)){
			$arrayLoan = array();
			$arrayLoan["LOANTYPE_CODE"] = $rowLoantype["loantype_code"];
			$getLoanTypeData = $conoracle->prepare("SELECT ln.LOANTYPE_DESC,lnt.interest_rate
													FROM lnloantype ln LEFT JOIN lncfloanintratedet lnt ON ln.inttabrate_code = lnt.loanintrate_code
													and sysdate BETWEEN lnt.effective_date and lnt.expire_date
													WHERE ln.loantype_code = :loantype_code");
			$getLoanTypeData->execute([':loantype_code' => $rowLoantype["loantype_code"]]);
			$rowLoanData = $getLoanTypeData->fetch(PDO::FETCH_ASSOC);
			$arrayLoan["LOANTYPE_DESC"] = $rowLoanData["LOANTYPE_DESC"];
			$arrayLoan["INT_RATE"] = $rowLoanData["INTEREST_RATE"] ?? 0;
			$arrayGrpLoan[] = $arrayLoan;
		}
		$arrayResult['LOAN_TYPE'] = $arrayGrpLoan;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>