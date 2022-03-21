<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrOldContract = array();
$fetchShare = $conoracle->prepare("SELECT LAST_PERIOD,(SHARESTK_AMT * 10) as SHARE_STK FROM SHSHAREMASTER WHERE MEMBER_NO = :member_no");
$fetchShare->execute([':member_no' => $member_no]);
$rowShare = $fetchShare->fetch(PDO::FETCH_ASSOC);
if($rowShare["LAST_PERIOD"] >= 6){
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
	$percentShare = $rowShare["SHARE_STK"] * 0.90;
	$percentSalary = $rowCredit["SALARY_AMOUNT"] * $rowCredit["MULTIPLE_SALARY"];
	$valueCredit = min($percentShare,$percentSalary);
	if($valueCredit > $rowCredit["MAXLOAN_AMT"]){
		$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
	}else{
		$maxloan_amt = $valueCredit;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "อัตราดอกเบี้ย";
	$arrSubOtherInfo["VALUE"] = "5.58 %";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = "150 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "กรณีสมาชิกอยู่ครบ 1 ปี (หลังจากวันที่ 31 ธันวาคม ของทุกปี) ให้นำดอกเบี้ยที่สมาชิกจ่ายให้กับสหกรณ์ทั้งปีไปคิดคำนวนเงินเฉลี่ยให้แก่สมาชิก ";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$getOldContract = $conoracle->prepare("SELECT lm.principal_balance,lt.loantype_desc,lm.loancontract_no FROM lncontmaster lm 
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
	}
	$arrCredit["OLD_CONTRACT"] = $arrOldContract;

}else{
	$maxloan_amt = 0;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกมาแล้วไม่ต่ำกว่า 6 เดือน";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
}

?>