<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 100000;
$getShareBF = $conoracle->prepare("SELECT (SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT FROM shsharemaster WHERE member_no = :member_no");
$getShareBF->execute([':member_no' => $member_no]);
$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowShareBF["SHAREBEGIN_AMT"] * 0.055;
if($maxloan_amt > $maxloanpermit_amt){
	$maxloan_amt = $maxloanpermit_amt;
}
$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
$receive_net = $maxloan_amt;
$canRequest = TRUE;
?>