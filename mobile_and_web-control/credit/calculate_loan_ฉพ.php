<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$getMemb = $conmssql->prepare("SELECT mb.membcat_code,mb.membtype_code,mg.MEMBGROUP_CONTROL,(sh.sharestk_amt*10) as SHARE_AMT,mb.SALARY_AMOUNT
											FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no 
											LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
											WHERE mb.member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
$fetchCredit = $conmssql->prepare("SELECT  loantype_code as LOANTYPE_CODE,maxloan_amt FROM   lnloantype lt, mbmembmaster WHERE member_no = :member_no   and LOANTYPE_CODE = :loantype_code");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt  = $rowCredit["maxloan_amt"];
if($maxloan_amt > $rowMemb["SHARE_AMT"]){
	$maxloan_amt = $rowMemb["SHARE_AMT"];
}
?>
