<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$collOnePerson = null;
$collTwoPerson = null;
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
if($maxloan_amt < 500000){
	$shareShouldHave = $rowMemb["SHARE_AMT"] * 0.12;
	if($shareShouldHave > $maxloan_amt){
		$maxloan_amt = $shareShouldHave;
	}
}else if($maxloan_amt >= 500000 && $maxloan_amt <= 1000000){
	$shareShouldHave = $rowMemb["SHARE_AMT"] * 0.17;
	if($shareShouldHave > $maxloan_amt){
		$maxloan_amt = $shareShouldHave;
	}
}else if($maxloan_amt > 1000000){
	$shareShouldHave = $rowMemb["SHARE_AMT"] * 0.22;
	if($shareShouldHave > $maxloan_amt){
		$maxloan_amt = $shareShouldHave;
	}
}
$arrCreditOnePerson[] = $rowMemb["SALARY_AMOUNT"] * 38;
$arrCreditOnePerson[] = 600000 + $rowMemb["SHARE_AMT"];
$creditOnePerson = min($arrCreditOnePerson);
if($maxloan_amt > $creditOnePerson){
	$collOnePerson = $creditOnePerson;
}else{
	$collOnePerson = $maxloan_amt;
}
$arrCreditTwoPerson[] = $rowMemb["SALARY_AMOUNT"] * 50;
$arrCreditTwoPerson[] = 1200000 + $rowMemb["SHARE_AMT"];
$creditTwoPerson = min($arrCreditTwoPerson);
if($maxloan_amt > $creditTwoPerson){
	$collTwoPerson = $creditTwoPerson;
}else{
	$collTwoPerson = $maxloan_amt;
}
if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
}
if(isset($collOnePerson)){
	$arrSubCollPerson["LABEL"] = "สิทธิ์การกู้สำหรับคนค้ำคนเดียว";
	$arrSubCollPerson["CREDIT_AMT"] = $collOnePerson;
	$arrCollShould[] = $arrSubCollPerson;
}
if(isset($collTwoPerson)){
	$arrSubCollPerson["LABEL"] = "สิทธิ์การกู้สำหรับคนค้ำมากกว่า 1 คน";
	$arrSubCollPerson["CREDIT_AMT"] = $collTwoPerson;
	$arrCollShould[] = $arrSubCollPerson;
}
$arrSubOther["VALUE"] = "สิทธิการกู้ที่แสดงในระบบนี้เป็นเพียงสิทธิประมาณการเท่านั้น  มิใช่สิทธิกู้จริง  ทางสหกรณ์จะต้องดูรายละเอียดต่าง ๆ ประกอบในการให้กู้แต่ละประเภทนั้น  ๆ อีกครั้ง";
$arrOtherInfo[] = $arrSubOther;
?>