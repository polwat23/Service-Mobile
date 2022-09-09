<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrOldContract = array();
$fetchShare = $conmssql->prepare("SELECT LAST_PERIOD,(SHARESTK_AMT * 10) as SHARE_STK FROM SHSHAREMASTER WHERE MEMBER_NO = :member_no");
$fetchShare->execute([':member_no' => $member_no]);
$rowShare = $fetchShare->fetch(PDO::FETCH_ASSOC);
if($rowShare["SHARE_STK"] > 0){
	$fetchCredit = $conmssql->prepare("SELECT TOP 1 lt.loantype_code as LOANTYPE_CODE,lt.loantype_desc AS LOANTYPE_DESC,lc.MAXLOAN_AMT,lc.MULTIPLE_SHARE,lc.MULTIPLE_SALARY,
											ISNULL(mb.salary_amount,15000) as SALARY_AMOUNT ,mb.MEMBER_DATE
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
											WHERE mb.member_no = :member_no and 
											LT.LOANTYPE_CODE = :loantype_code
											and DATEDIFF(month,mb.member_date,getDate()) BETWEEN lc.startmember_time and lc.endmember_time ORDER BY  lc.maxloan_amt DESC");
	$fetchCredit->execute([
		':member_no' => $member_no,
		':loantype_code' => $loantype_code
	]);
	$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
	$member_date_count = $lib->count_duration($rowCredit["MEMBER_DATE"],"m");
	$percentShare = $rowShare["SHARE_STK"];
	
	if($member_date_count >= 6 &&  $member_date_count <= 7){
		$maxloan_amt = 10000; 
	}else if($member_date_count >= 7 &&  $member_date_count <= 12){
		$maxloan_amt = 30000; 
	}else if($member_date_count >= 13 &&  $member_date_count <= 35 && $percentShare >= 30000){
		$maxloan_amt = 60000; 
	}else if($member_date_count >= 36 && $percentShare >= 60000){
		$maxloan_amt = 120000; 	
	}else{
		$maxloan_amt = 0;
		$arrSubOtherInfo = array();
		$arrSubOtherInfo["LABEL"] = "ไม่มีสิทธิ์กู้เนื่องจากหุ้นไม่ถึงตามเกณฑ์";
		$arrSubOtherInfo["VALUE"] = "";
		$arrOtherInfo[] = $arrSubOtherInfo;
		$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
	}
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "อัตราดอกเบี้ย";
	$arrSubOtherInfo["VALUE"] = "6.35 %";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = "12 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
	$receive_net = $maxloan_amt;
	$canRequest = TRUE;

}else{
	$maxloan_amt = 0;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกมาแล้วไม่ต่ำกว่า 3 เดือน";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
}

?>
