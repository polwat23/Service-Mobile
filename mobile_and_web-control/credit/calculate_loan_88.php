<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrSubOtherInfo = array();
$arrSubOtherInfoSalaryRemain = array();
$getMemberData = $conoracle->prepare("SELECT member_date,salary_amount FROM mbmembmaster WHERE member_no = :member_no");
$getMemberData->execute([':member_no' => $member_no]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);
$duration_month = $lib->count_duration($rowMembData["MEMBER_DATE"],'m');
$fetchCredit = $conoracle->prepare("SELECT startmember_time,maxloan_amt FROM lnloantypecustom WHERE loantype_code = :loantype_code");
$fetchCredit->execute([
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$salary_amount = $rowMembData["SALARY_AMOUNT"] * 1.5;
$loanpermitArr = [];
$loanpermitArr[] = $rowCredit["MAXLOAN_AMT"];
$loanpermitArr[] = $salary_amount;
$maxloan_amt = min($loanpermitArr);

$canRequest = TRUE;
?>