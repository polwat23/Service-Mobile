<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;

$getMemberData = $conoracle->prepare("SELECT mb.MEMBER_DATE,mb.SALARY_AMOUNT,(sh.SHARESTK_AMT * 10) as SHARE_BAL,
										TRUNC(MONTHS_BETWEEN(ADD_MONTHS( mb.birth_date, 720) , sysdate),0) AS PERIOD_REMAIN
										FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no and mb.branch_id = sh.branch_id
										WHERE mb.member_no = :member_no and mb.branch_id = :branch_id");
$getMemberData->execute([
	':member_no' => $member_no,
	':branch_id' => $payload["branch_id"]
]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);
$member_duration = $lib->count_duration($rowMembData["MEMBER_DATE"],'m');
$period_remain = $rowMembData["PERIOD_REMAIN"];
$getLoantypeCustom = $conoracle->prepare("SELECT PERCENTSALARY, PERCENTSHARE, MAXLOAN_AMT 
										FROM lnloantypecustom 
										WHERE loantype_code = :loantype_code and
										:member_duration >= STARTMEMBER_TIME and :member_duration < ENDMEMBER_TIME and
										:share_bal >= STARTSHARE_AMT and :share_bal < ENDSHARE_AMT");
$getLoantypeCustom->execute([
	':loantype_code' => $loantype_code,
	':member_duration' => $member_duration,
	':share_bal' => $rowMembData["SHARE_BAL"]
]);
$rowLTCustom = $getLoantypeCustom->fetch(PDO::FETCH_ASSOC);
if(isset($rowLTCustom["MAXLOAN_AMT"])){
	$arrWeight = array();
	if($rowLTCustom["PERCENTSHARE"] > 0){
		$arrWeight[] = $rowMembData["SHARE_BAL"] * $rowLTCustom["PERCENTSHARE"];
	}
	if($rowLTCustom["PERCENTSALARY"] > 0){
		$arrWeight[] = $rowMembData["SALARY_AMOUNT"] * $rowLTCustom["PERCENTSALARY"];
	}
	$maxloan_amt = min($arrWeight);
	if($maxloan_amt > $rowLTCustom["MAXLOAN_AMT"]){
		$maxloan_amt = (int)$rowLTCustom["MAXLOAN_AMT"];
	}
	$getSalaryMin = $conoracle->prepare("SELECT SALPERCT_BALANCE,SALAMT_BALANCE,RETRYCOLLCHK_FLAG,RETRYLOANSEND_TIME 
										FROM lnloantype WHERE loantype_code = :loantype_code");
	$getSalaryMin->execute([':loantype_code' => $loantype_code]);
	$rowSalaryMin = $getSalaryMin->fetch(PDO::FETCH_ASSOC);
	$getPeriodMax = $conoracle->prepare("SELECT MAX_PERIOD FROM lnloantypeperiod
										WHERE loantype_code = :loantype_code and :member_duration >= money_from and :member_duration < money_to");
	$getPeriodMax->execute([
		':loantype_code' => $loantype_code,
		':member_duration' => $member_duration
	]);
	$rowPeriod = $getPeriodMax->fetch(PDO::FETCH_ASSOC);
	if($rowSalaryMin["RETRYCOLLCHK_FLAG"] == '1' || $rowSalaryMin["RETRYCOLLCHK_FLAG"] == '2'){
		$maxperiod = $period_remain + $rowSalaryMin["RETRYLOANSEND_TIME"];
		if($maxperiod > $rowPeriod["MAX_PERIOD"]){
			$maxperiod = $rowPeriod["MAX_PERIOD"];
		}
	}else{
		$maxperiod = $rowPeriod["MAX_PERIOD"];
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = $maxperiod." งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "เงินเดือนคงเหลืออย่างน้อย ".($rowSalaryMin["SALPERCT_BALANCE"] * 100)."% หลังหักชำระต่องวดเรียบร้อย";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "เงินเดือนคงเหลือต้องไม่น้อยกว่า ".number_format($rowSalaryMin["SALAMT_BALANCE"],0)." บาทหลังหักชำระต่องวดเรียบร้อย";
	$arrOtherInfo[] = $arrSubOtherInfo;
}else{
	$maxloan_amt = 0;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ไม่สามารถกู้ประเภทนี้ได้ต้องเป็นสมาชิกอย่างน้อย ".$rowLTCustom["STARTMEMBER_TIME"]." เดือนขึ้นไปและต้องไม่เกิน ".number_format($rowLTCustom["ENDMEMBER_TIME"],0)." เดือน";
	$arrCollShould[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ไม่สามารถกู้ประเภทนี้ได้ต้องมีหุ้นอย่างน้อย ".number_format($rowLTCustom["STARTSHARE_AMT"],2)." บาทขึ้นไปและต้องไม่เกิน ".
	number_format($rowLTCustom["ENDSHARE_AMT"],2)." บาท";
	$arrCollShould[] = $arrSubOtherInfo;
}
?>