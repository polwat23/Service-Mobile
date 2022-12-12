<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$getDeptATM = $conoracle->prepare("SELECT DEPTACCOUNT_NO FROM dpdeptmaster WHERE depttype_code = '88' and deptclose_status = 0 and member_no = :member_no");
$getDeptATM->execute([':member_no' => $member_no]);
$rowDept = $getDeptATM->fetch(PDO::FETCH_ASSOC);
if(isset($rowDept["DEPTACCOUNT_NO"]) && $rowDept["DEPTACCOUNT_NO"] != ""){
	$getSalaryAmt = $conoracle->prepare("SELECT NVL(salary_amount,0) as SALARY_AMOUNT ,NVL(incomeetc_amt,0) as INCOMEETC_AMT   FROM mbmembmaster WHERE member_no = :member_no");
	$getSalaryAmt->execute([':member_no' => $member_no]);
	$rowSalary = $getSalaryAmt->fetch(PDO::FETCH_ASSOC);
	$salary_amount = $rowSalary["SALARY_AMOUNT"] + $rowSalary["INCOMEETC_AMT"];
	$maxloan_amt = $salary_amount * 3;
	if($maxloan_amt > 100000){
		$maxloan_amt = 100000;
	}

	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = "12 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;

	$canRequest = TRUE;
}else{
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องมีบัญชีเงินฝากออมทรัพย์เอทีเอ็ม(ATM)";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$maxloan_amt = 0;
	$canRequest = FALSE;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
}
?>