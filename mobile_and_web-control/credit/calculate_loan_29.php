<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$minsalary = 0;
$moneycoop  = 0 ;
$request_amt = 0;
$loan_permit_amt = 0;
$getShare = $conoracle->prepare("SELECT  (sharestk_amt * 10) as SHARE_AMT ,  (periodshare_amt * 10) as PERIODSHARE_AMT  FROM shsharemaster WHERE member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);

$getLoan = $conoracle->prepare("SELECT sum(period_payment) as PERIOD_PAYMENT from lncontmaster WHERE member_no = :member_no AND contract_status > 0 AND contract_status <> 8");
$getLoan->execute([':member_no' => $member_no]);
$rowLoan = $getLoan->fetch(PDO::FETCH_ASSOC);



$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt,lc.multiple_salary,lc.multiple_share,NVL(mb.salary_amount,15000) as salary_amount,lc.startmember_time,mb.member_date
									FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
									WHERE mb.member_no = :member_no and 
									LT.LOANTYPE_CODE = :loantype_code
									and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$member_date_count = $lib->count_duration($rowCredit["MEMBER_DATE"],"m");
if($member_date_count > 2){
	$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
	$loan_permit_amt = $rowCredit["MAXLOAN_AMT"];
}else{
	$maxloan_amt = 0;
}

$getOldContract = $conoracle->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY 
									FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
									WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8 
									and lm.loantype_code = :loantype_code");
$getOldContract->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC);
if(isset($rowOldContract["LOANCONTRACT_NO"]) && $rowOldContract["LOANCONTRACT_NO"] != ""){
	$arrContract = array();
	$oldBal += $rowOldContract["PRINCIPAL_BALANCE"] ;
	$canRequest = TRUE;
}else{
	$canRequest = TRUE;
}
?>
