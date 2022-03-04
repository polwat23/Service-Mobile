<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 1;
$oldBal = 0;
$request_amt = 0;
	$getSalaryAmt = $conoracle->prepare("SELECT NVL(salary_amount,0) as SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
	$getSalaryAmt->execute([':member_no' => $member_no]);
	$rowSalary = $getSalaryAmt->fetch(PDO::FETCH_ASSOC);
	$getShare = $conoracle->prepare("SELECT LAST_PERIOD FROM shsharemaster where member_no = :member_no");
	$getShare->execute([':member_no' => $member_no]);
	$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);
	$percent = 20;
	$salary = $rowSalary["SALARY_AMOUNT"];

	$maxloan_amt = $salary * $percent;
	if($maxloan_amt > 400000){
		$maxloan_amt = 400000;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = "150 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
	if($rowShare["LAST_PERIOD"] < 6){
		$maxloan_amt = 0;
		$arrSubOtherInfo = array();
		$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกและชำระค่าหุ้นรายเดือนติดต่อกันไม่น้อยกว่า 6 เดือน";
		$arrSubOtherInfo["VALUE"] = "";
		$arrOtherInfo[] = $arrSubOtherInfo;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "แนบโครงการในการที่จะประกอบอาชีพและต้องมีผู้ค้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 2 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$canRequest = FALSE;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
?>
