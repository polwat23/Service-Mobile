<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$getShareBF = $conoracle->prepare("SELECT (SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT FROM shsharemaster WHERE member_no = :member_no");
$getShareBF->execute([':member_no' => $member_no]);
$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
$getLoanCerdit = $conoracle->prepare("SELECT multiple_share,maxloan_amt FROM lnloantypecustom WHERE loantype_code = :loantype_code");
$getLoanCerdit->execute([':loantype_code' => $loantype_code]);
$rowLoanCredit = $getLoanCerdit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowShareBF["SHAREBEGIN_AMT"] * $rowLoanCredit["MULTIPLE_SHARE"];
if($maxloan_amt > $rowLoanCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowLoanCredit["MAXLOAN_AMT"];
}

?>
