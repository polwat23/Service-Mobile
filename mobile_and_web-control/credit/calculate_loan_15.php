<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$memb_duration = $memb_duration ?? 0;
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$shareColl = null;
$credit_salary = 0;

$getMemberData = $conoracle->prepare("SELECT (sh.sharestk_amt * 50) as SHARE_BALANCE,mb.SALARY_AMOUNT,sh.LAST_PERIOD
									FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no WHERE mb.member_no = :member_no");
$getMemberData->execute([':member_no' => $member_no]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);

if($memb_duration >= 12 && $memb_duration < 36){
	$maxloan_amt = 250000;
	$credit_salary = $rowMembData["SALARY_AMOUNT"] * 20;
}else if($memb_duration >= 36 && $memb_duration < 60){
	$maxloan_amt = 600000;
	$credit_salary = $rowMembData["SALARY_AMOUNT"] * 30;
}else if($memb_duration >= 60 && $memb_duration < 120){
	$maxloan_amt = 1200000;
	$credit_salary = $rowMembData["SALARY_AMOUNT"] * 45;
}else if($memb_duration >= 120 && $memb_duration < 144){
	$maxloan_amt = 1800000;
	$credit_salary = $rowMembData["SALARY_AMOUNT"] * 55;
}else if($memb_duration >= 144 && $memb_duration < 999){
	$maxloan_amt = 2500000;
	$credit_salary = $rowMembData["SALARY_AMOUNT"] * 60;
}
$loanpermitArr = [];
$loanpermitArr[] = $maxloan_amt;
$loanpermitArr[] = $credit_salary;
$maxloan_amt = min($loanpermitArr);
?>