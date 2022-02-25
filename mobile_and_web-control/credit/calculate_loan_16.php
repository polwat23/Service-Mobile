<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 400000;
$oldBal = 0;
$request_amt = 0;
$collNotOver2M = null;
$collNotOver3M = null;
$getMemberData = $conoracle->prepare("SELECT (sh.sharestk_amt * 50) as SHARE_BALANCE,mb.SALARY_AMOUNT,sh.LAST_PERIOD
									FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no WHERE mb.member_no = :member_no");
$getMemberData->execute([':member_no' => $member_no]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);
$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt,lc.multiple_share,lc.multiple_salary FROM lnloantypecustom lc,mbmembmaster mb 
									LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no WHERE mb.member_no = :member_no and lc.LOANTYPE_CODE = :loantype_code
									and sh.LAST_PERIOD BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
$avai_shr = $rowMembData["SHARE_BALANCE"] * 0.9;
$loanpermitArr = [];
$loanpermitArr[] = $maxloan_amt;
$loanpermitArr[] = $avai_shr;
$maxloan_amt = min($loanpermitArr);
?>