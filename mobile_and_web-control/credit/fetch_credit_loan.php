<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupCredit = array();
		$arrCanCal = array();
		$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_creditloan = '1'");
		$fetchLoanCanCal->execute();
		while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
			$arrCanCal[] = $rowCanCal["loantype_code"];
		}
		$fetchCredit = $conoracle->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt,lc.percentshare,lc.percentsalary,mb.salary_amount,(sh.sharestk_amt*10) as SHARE_AMT
													FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb 
													LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
													WHERE mb.member_no = :member_no and 
													LT.LOANTYPE_CODE IN(".implode(',',$arrCanCal).")
													and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
		$fetchCredit->execute([':member_no' => $member_no]);
		while($rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC)){
			$arrCredit = array();
			$maxloan_amt = 0;
			$salaryPercent = $rowCredit["SALARY_AMOUNT"] * $rowCredit["PERCENTSALARY"];
			$sharePercent = $rowCredit["SHARE_AMT"] * $rowCredit["PERCENTSHARE"];
			if($salaryPercent > $sharePercent){
				$maxloan_amt = $salaryPercent;
			}else{
				$maxloan_amt = $sharePercent;
			}
			if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
				$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
			}
			$arrCredit["LOAN_DESC"] = $rowCredit["LOANTYPE_DESC"];
			$arrCredit["MAXLOAN_AMT"] = number_format($maxloan_amt,2);
			$arrGroupCredit[] = $arrCredit;
		}
		$arrayResult["CREDIT"] = $arrGroupCredit;
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