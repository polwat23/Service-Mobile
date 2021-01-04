<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$getMemb = $conmssql->prepare("SELECT mb.MEMBTYPE_CODE,mg.MEMBGROUP_CONTROL,(sh.sharestk_amt*10) as SHARE_AMT,mb.SALARY_AMOUNT
											FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no 
											LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
											WHERE mb.member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
$fetchCredit = $conmssql->prepare("SELECT lt.loantype_code as LOANTYPE_CODE,lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt,lc.percentshare,lc.percentsalary
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
											WHERE mb.member_no = :member_no and 
											LT.LOANTYPE_CODE = :loantype_code
											and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$salaryPercent = $rowMemb["SALARY_AMOUNT"] * $rowCredit["PERCENTSALARY"];
if($rowCredit["PERCENTSHARE"] > 0){
	$sharePercent = $rowMemb["SHARE_AMT"] * $rowCredit["PERCENTSHARE"];
	if($salaryPercent < $sharePercent){
		$maxloan_amt = $salaryPercent;
	}else{
		$maxloan_amt = $sharePercent;
	}
}else{
	$maxloan_amt = $salaryPercent;
}
if($maxloan_amt > $rowMemb["SHARE_AMT"]){
	$maxloan_amt = $rowMemb["SHARE_AMT"];
}
?>