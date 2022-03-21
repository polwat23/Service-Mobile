<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrOldContract = array();
$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt,lc.multiple_salary,NVL(mb.salary_amount,15000) as salary_amount
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
											WHERE mb.member_no = :member_no and 
											LT.LOANTYPE_CODE = :loantype_code
											and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$percentShare = $rowShare["SHARE_STK"];
$percentSalary = $rowCredit["SALARY_AMOUNT"] * $rowCredit["MULTIPLE_SALARY"];
$valueCredit = min($percentShare,$percentSalary);
if($valueCredit > $rowCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
}else{
	$maxloan_amt = $valueCredit;
}
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "อัตราดอกเบี้ย";
$arrSubOtherInfo["VALUE"] = "5 %";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
$arrSubOtherInfo["VALUE"] = "180 งวด";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "";
$arrSubOtherInfo["VALUE"] = "ผู้ค้ำประกัน";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "เงินกู้ตั้งแต่จำนวน 1,000,000 บาท (หนึ่งล้านบาทถ้วน) ขึ้นไปต้องมีผู้ค้ำประกันไม่น้อยกว่า 2 คน";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "สมาชิกผู้ค้ำประกันต้องเป็นสมาชิกมาแล้ว ไม่น้อยกว่า 6 เดือน และต้องผ่านการประเมินผลการทดลองปฏิบัติงานจากหน่วยงานต้นสังกัด รวมทั้งต้องไม่เป็นคู่สมรสของสมาชิกผู้กู้";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "สมาชิกที่เป็นคู่สมรสกัน จะค้ำประกันสมาชิกผู้กู้รายเดียวกันร่วมกันไม่ได้";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "สมาชิกผู้ค้ำประกันสามารถค้ำประกันได้ไม่เกินวงเงินกู้ของตน";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "สมาชิกผู้ค้ำประกัน สามารถค้ำประกันได้ไม่เกินวันที่ตนเองเกษียณอายุ เว้นแต่สมาชิกผู้ค้ำประกันซึ่งมีสิทธิได้รับบำนาญหรือบำเหน็จรายเดือน ให้สามารถค้ำประกันได้ไม่เกินอายุ70 ปี";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "สมาชิกคนหนึ่งจะค้ำประกันสมาชิกผู้กู้คนอื่นในเวลาเดียวกันได้ไม่เกิน 4 คน";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "สมาชิกผู้ค้ำประกันกับสมาชิกผู้กู้ จะค้ำประกันไขว้กันไม่ได้ เว้นแต่ มีสมาชิกอื่นเป็นผู้ค้ำประกันเพิ่มอีก 1 คน";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$getOldContract = $conoracle->prepare("SELECT lm.principal_balance,lt.loantype_desc,lm.loancontract_no,lm.last_periodpay FROM lncontmaster lm 
										LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.loantype_code = '02'");
$getOldContract->execute([
	':member_no' => $member_no
]);
while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
	$arrContract = array();
	$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
	$arrContract["CONTRACT_NO"] = $rowOldContract["LOANCONTRACT_NO"];
	$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
	$oldBal += $rowOldContract["PRINCIPAL_BALANCE"];
	$arrOldContract[] = $arrContract;
	if($rowOldContract["LAST_PERIODPAY"] < 6){
		$arrSubOtherInfo = array();
		$arrSubOtherInfo["LABEL"] = "ต้องชำระหนี้เงินกู้สามัญเดิม ไม่น้อยกว่า";
		$arrSubOtherInfo["VALUE"] = "6 งวด";
		$arrOtherInfo[] = $arrSubOtherInfo;
		$maxloan_amt = 0;
	}
}
$arrCredit["OLD_CONTRACT"] = $arrOldContract;
	

?>