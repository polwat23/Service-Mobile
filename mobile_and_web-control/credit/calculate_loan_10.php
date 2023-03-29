<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$getMemb = $conoracle->prepare("SELECT SALARY_AMOUNT,MEMBER_DATE,trunc(MONTHS_BETWEEN(ADD_MONTHS(birth_date, 720),sysdate),0) as REMAIN_PERIOD 
								FROM mbmembmaster WHERE member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
$month_member = $lib->count_duration($rowMemb["MEMBER_DATE"],'m');
$age_member = intval($month_member / 12);
if($age_member >= 3){
	$getLoanCustom = $conoracle->prepare("SELECT PERCENTSALARY,MAXLOAN_AMT FROM lnloantypecustom 
										WHERE loantype_code = :loantype_code and :month_member BETWEEN startmember_time and endmember_time");
	$getLoanCustom->execute([
		':loantype_code' => $loantype_code,
		':month_member' => $month_member
	]);
	$rowLoanCustom = $getLoanCustom->fetch(PDO::FETCH_ASSOC);
	$maxloan_amt = $rowLoanCustom["PERCENTSALARY"] * $rowMemb["SALARY_AMOUNT"];
	if($maxloan_amt > $rowLoanCustom["MAXLOAN_AMT"]){
		$maxloan_amt = $rowLoanCustom["MAXLOAN_AMT"];
	}
	$getOldContract = $conoracle->prepare("SELECT ln.LOANCONTRACT_NO,ln.PRINCIPAL_BALANCE,lt.LOANTYPE_DESC 
										FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.loantype_code = lt.loantype_code
										WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8
										and lt.loangroup_code = '02' and ln.loantype_code NOT IN('2H','2G','2F')");
	$getOldContract->execute([':member_no' => $member_no]);
	while($rowContDetail = $getOldContract->fetch(PDO::FETCH_ASSOC)){
		$arrContract = array();
		$arrContract['LOANTYPE_DESC'] = $rowContDetail["LOANTYPE_DESC"];
		$arrContract['CONTRACT_NO'] = $rowContDetail["LOANCONTRACT_NO"];
		$arrContract['BALANCE'] = $rowContDetail["PRINCIPAL_BALANCE"];
		$arrOldContract[] = $arrContract;
	}
	$getPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM LNLOANTYPEPERIOD 
									WHERE LOANTYPE_CODE = :loantype_code");
	$getPeriod->execute([':loantype_code' => $loantype_code]);
	$rowPeriod = $getPeriod->fetch(PDO::FETCH_ASSOC);
	$arrSubOther["LABEL"] = "งวดสูงสุด";
	$max_period = $rowMemb["REMAIN_PERIOD"];
	if($max_period > $rowPeriod["MAX_PERIOD"]){
		$max_period = $rowPeriod["MAX_PERIOD"];
	}
	if($max_period < 0){
		$max_period = 0;
	}
	$arrSubOther["VALUE"] = $max_period." งวด";
	$arrOtherInfo[] = $arrSubOther;
}else{
	$maxloan_amt = 0;
	$arrSubOther["LABEL"] = "ต้องเป็นสมาชิกอย่างน้อย 6 เดือนขึ้นไป";
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
	$arrCollShould[] = $arrSubOther;
}
?>