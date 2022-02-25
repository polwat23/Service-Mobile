<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$is_s_group = $is_s_group ?? false;
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrSubOtherInfo = array();
$arrSubOtherInfoSalaryRemain = array();
$getMemberData = $conoracle->prepare("SELECT member_date,salary_amount FROM mbmembmaster WHERE member_no = :member_no");
$getMemberData->execute([':member_no' => $member_no]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);
$salary_amount = $rowMembData["SALARY_AMOUNT"] * 1.5;
$loanpermitArr = [];
if($is_s_group){
	$getShareData = $conoracle->prepare("SELECT (sh.sharestk_amt * 50) as SHARE_BALANCE
										FROM shsharemaster sh WHERE sh.member_no = :member_no");
	$getShareData->execute([':member_no' => $member_no]);
	$rowShareData = $getShareData->fetch(PDO::FETCH_ASSOC);
	$loanpermitArr[] = $rowShareData["SHARE_BALANCE"] * 0.90;
}
$loanpermitArr[] = $salary_amount;
$maxloan_amt = min($loanpermitArr);

$canRequest = TRUE;
?>