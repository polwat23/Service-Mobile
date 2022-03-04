<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$getOldContract = $conoracle->prepare("SELECT PRINCIPAL_BALANCE FROM LNCONTMASTER
										WHERE member_no = :member_no and loantype_code = '21' and contract_status = '1'");
$getOldContract->execute([':member_no' => $member_no]);
$rowOldCont = $getOldContract->fetch(PDO::FETCH_ASSOC);
$oldBal = $rowOldCont["PRINCIPAL_BALANCE"] ?? 0;
$getSalaryAmt = $conoracle->prepare("SELECT NVL(salary_amount,0) as SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
$getSalaryAmt->execute([':member_no' => $member_no]);
$rowSalary = $getSalaryAmt->fetch(PDO::FETCH_ASSOC);
$getShare = $conoracle->prepare("SELECT LAST_PERIOD FROM shsharemaster where member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);
$percent = 0.5;
$salary = $rowSalary["SALARY_AMOUNT"];

$maxloan_amt = $salary * $percent;
if($maxloan_amt > 10000){
	$maxloan_amt = 10000;
}
if($rowShare["LAST_PERIOD"] < 3){
	$maxloan_amt = 0;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกและชำระค่าหุ้นรายเดือนติดต่อกันไม่น้อยกว่า 3 เดือน";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
}
$receive_net = $maxloan_amt - $oldBal;
if($receive_net < 0){
	$receive_net = 0;
}
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
$arrSubOtherInfo["VALUE"] = "4 งวด";
$arrOtherInfo[] = $arrSubOtherInfo;
$canRequest = TRUE;
$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
?>
