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
	$memberInfo = $conoracle->prepare("SELECT BIRTH_DATE
											FROM mbmembmaster
											WHERE member_no = :member_no");
	$memberInfo->execute([':member_no' => $member_no]);
	$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
	$age = $lib->count_duration($rowMember["BIRTH_DATE"],"y");
	$percent = 65;
	$salary = $rowSalary["SALARY_AMOUNT"];
	if($age > 55 && $age < 60){
		if($age == 56){
			$salary = $salary * 0.95;
		}else if($age == 57){
			$salary = $salary * 0.90;
		}else if($age == 58){
			$salary = $salary * 0.85;
		}else if($age == 59){
			$salary = $salary * 0.80;
		}
	}
	$maxloan_amt = $salary * $percent;
	if($maxloan_amt > 5150000){
		$maxloan_amt = 5150000;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = "240 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
	if($rowShare["LAST_PERIOD"] < 6){
		$maxloan_amt = 0;
		$arrSubOtherInfo = array();
		$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกและชำระค่าหุ้นรายเดือนติดต่อกันไม่น้อยกว่า 6 เดือน";
		$arrSubOtherInfo["VALUE"] = "";
		$arrOtherInfo[] = $arrSubOtherInfo;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องถือหุ้นไม่น้อยกว่าร้อยละ 20 ของจำนวนที่จะขอกู้";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ผู้กู้ที่ขอกู้เงินไม่เกิน  500,000 บาท ต้องมีผู้ค้้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 2 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ผู้กู้ที่ขอกู้เงินเกินกว่า 500,000 บาท ถึง 1,000,000 บาท ต้องมีผู้ค้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 3 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ผู้กู้ที่ขอกู้เงินเกินกว่า 1,000,000 บาท ถึง 2,700,000 บาท ต้องมีผู้ค้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 4 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ผู้กู้ที่ขอกู้เงินเกินกว่า 2,700,000 บาท ถึง 3,700,000 บาท ต้องมีผู้ค้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 5 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ผู้กู้ที่ขอกู้เงินเกินกว่า 3,700,000 บาท ถึง 4,700,000 บาท ต้องมีผู้ค้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 6 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ผู้กู้ที่ขอกู้เงินเกินกว่า 4,700,000 บาท ถึง 5,150,000 บาท ต้องมีผู้ค้ำประกันไม่น้อยกว่า";
	$arrSubOtherInfo["VALUE"] = " 7 คน";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$canRequest = FALSE;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
?>
