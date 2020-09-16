<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$getShareBF = $conoracle->prepare("SELECT (sh.SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT,mb.SALARY_AMOUNT 
												FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
												WHERE mb.member_no = :member_no");
$getShareBF->execute([':member_no' => $member_no]);
$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
$getLoanCerdit = $conoracle->prepare("SELECT 
												lc.maxloan_amt,(SELECT 
												lc.multiple_salary
												FROM lnloantypecustom lc,mbmembmaster mb 
												WHERE mb.member_no = :member_no and 
												lc.LOANTYPE_CODE = :loantype_code
												and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time and lc.multiple_salary > 0 ) as PERCENT_SALARY,
												(SELECT 
												lc.multiple_share
												FROM lnloantypecustom lc,mbmembmaster mb 
												WHERE mb.member_no = :member_no and 
												lc.LOANTYPE_CODE = :loantype_code
												and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time and lc.multiple_share > 0 ) as PERCENT_SHARE
												FROM lnloantypecustom lc,mbmembmaster mb 
												WHERE mb.member_no = :member_no and 
												lc.LOANTYPE_CODE = :loantype_code
												and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time and rownum <= 1");
$getLoanCerdit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowLoanCredit = $getLoanCerdit->fetch(PDO::FETCH_ASSOC);
$arrMin = array();
$arrMin[] = $rowShareBF["SHAREBEGIN_AMT"] * $rowLoanCredit["PERCENT_SHARE"];
$arrMin[] = $rowShareBF["SALARY_AMOUNT"] * $rowLoanCredit["PERCENT_SALARY"];
$maxloan_amt = MIN($arrMin);
if($maxloan_amt > $rowLoanCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowLoanCredit["MAXLOAN_AMT"];
}

?>
