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
	$percent = 95;
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
	$arrSubOtherInfo["VALUE"] = "360 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
	if($rowShare["LAST_PERIOD"] < 6){
		$maxloan_amt = 0;
		$arrSubOtherInfo = array();
		$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกและชำระค่าหุ้นรายเดือนติดต่อกันไม่น้อยกว่า 24 เดือน";
		$arrSubOtherInfo["VALUE"] = "";
		$arrOtherInfo[] = $arrSubOtherInfo;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องถือหุ้นไม่น้อยกว่าร้อยละ 20 ของจำนวนที่จะขอกู้";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "";
	$arrSubOtherInfo["VALUE"] = "หลักฐานประกอบการขอกู้";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "โฉนดที่ดิน หรือ น.ส.3 ก";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ใบประเมินราคาที่ดินของสำนักงานที่ดิน ที่ประเมินตามบัญชีกำหนดราคาประเมินทุนทรัพย์ที่ดินของกรมธนารักษ์ไม่เกิน 180 วัน";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "สำเนาบัตรประจำตัวประชาชน ,สำเนาทะเบียนบ้าน ,สำเนาใบสำคัญการสมรส(กรณีมีคู่สมรส) ของผู้กู้ และหรือผู้ถือกรรมสิทธิ์ที่ดิน";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "หนังสือยินยอมให้หักเงินปันผล ,เงินเฉลี่ยคืน ,เงินประกันอัคคีภัย ,เงินประกันชีวิต";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "สลิปเงินเดือน 3 เดือนย้อนหลัง";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ภาพถ่ายสิ่งปลูกสร้างด้านหน้าและด้านข้าง ขนาด 3x5 นิ้ว ใช้กระดาษอัดภาพสีพร้อมรับรองความถูกต้อง จำนวน 2 แผ่น";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "แผนผังที่ตั้งของหลักประกันที่จะนำมาจำนอง เป็นประกันทุกแปลง";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$canRequest = FALSE;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
?>
