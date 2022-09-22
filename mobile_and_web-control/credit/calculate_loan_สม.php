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

$getLoantype =  $conmssql->prepare("select  loanright_type from lnloantype where loantype_code = :loantype_code");
$getLoantype->execute([':loantype_code' => $loantype_code]);
$rowLoantype= $getLoantype->fetch(PDO::FETCH_ASSOC);
if($rowLoantype["loanright_type"] == "0"){
	$fetchCredit = $conmssql->prepare("SELECT  loantype_code as LOANTYPE_CODE,maxloan_amt FROM   lnloantype lt, mbmembmaster WHERE member_no = :member_no   and LOANTYPE_CODE = :loantype_code");
	$fetchCredit->execute([ ':member_no' => $member_no,
		':loantype_code' => $loantype_code
	]);
}else{
	$fetchCredit = $conmssql->prepare("SELECT  lt.loantype_code as LOANTYPE_CODE,lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt,lc.MULTIPLE_SHARE as PERCENTSHARE,lc.MULTIPLE_SALARY as PERCENTSALARY
												FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
												WHERE mb.member_no = :member_no  
												and LT.LOANTYPE_CODE = :loantype_code
												and lc.membtype_code = :membtype_code
												and  lc.membcat_code = :membcat_code
												and DATEDIFF(month,mb.member_date,getDate()) BETWEEN lc.startmember_time and lc.endmember_time");
	$fetchCredit->execute([
		':member_no' => $member_no,
		':loantype_code' => $loantype_code,
		':membtype_code' => $rowMemb["membtype_code"],
		':membcat_code' => $rowMemb["membcat_code"]
	]);	
}
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$salaryPercent = $rowMemb["SALARY_AMOUNT"] * $rowCredit["PERCENTSALARY"];
if($rowCredit["PERCENTSHARE"] > 0){
	$sharePercent = $rowMemb["SHARE_AMT"] * $rowCredit["PERCENTSHARE"];
	if($sharePercent < $salaryPercent){
		if($salaryPercent  > $rowCredit["maxloan_amt"]){
			$maxloan_amt = $rowCredit["maxloan_amt"];
		}else{
			$maxloan_amt = $salaryPercent;
		}
	}else{
		if($sharePercent  > $rowCredit["maxloan_amt"]){
			$maxloan_amt = $rowCredit["maxloan_amt"];
		}else{
			$maxloan_amt = $sharePercent;
		}
	}
}else{
	$maxloan_amt = $rowCredit["maxloan_amt"];
}
?>
