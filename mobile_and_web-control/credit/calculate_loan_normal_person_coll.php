<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$getMemb = $conoracle->prepare("SELECT sh.LAST_PERIOD,(sh.sharestk_amt*10) as SHARE_AMT,mb.SALARY_AMOUNT
											FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
											WHERE mb.member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
$fetchCredit = $conoracle->prepare("SELECT MAXLOAN_AMT,PERCENTSALARY
											FROM lnloantypecustom
											WHERE 
											LOANTYPE_CODE = :loantype_code
											and ".$rowMemb["LAST_PERIOD"]." >= startmember_time and ".$rowMemb["LAST_PERIOD"]." < endmember_time");
$fetchCredit->execute([
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * $rowCredit["PERCENTSALARY"];
if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
}
?>