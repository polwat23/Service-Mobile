<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$loantint_rate = 0;
$multiple_share = 0;
$multiple_salary = 0;
$startshare_amt = 0;
$arrOldContract = array();
$fetchShare = $conmssql->prepare("SELECT LAST_PERIOD,(SHARESTK_AMT * 10) as SHARE_STK FROM SHSHAREMASTER WHERE MEMBER_NO = :member_no");
$fetchShare->execute([':member_no' => $member_no]);
$rowShare = $fetchShare->fetch(PDO::FETCH_ASSOC);
if($rowShare["SHARE_STK"] > 0){
	$fetchLoanIntRate = $conmssql->prepare("SELECT lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
														ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
														WHERE lnt.loantype_code = :loantype_code and GETDATE() BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
	$fetchLoanIntRate->execute([':loantype_code' =>  $loantype_code]);
	$rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC);
	$loantint_rate = $rowIntRate["INTEREST_RATE"];
	
	
	$fetchCredit = $conmssql->prepare("SELECT TOP 1 lt.loantype_code as LOANTYPE_CODE,lt.loantype_desc AS LOANTYPE_DESC,lc.MAXLOAN_AMT,lc.MULTIPLE_SHARE,lc.MULTIPLE_SALARY,
											ISNULL(mb.salary_amount,15000) as SALARY_AMOUNT ,mb.MEMBER_DATE ,lc.STARTSHARE_AMT
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
											WHERE mb.member_no = :member_no and 
											LT.LOANTYPE_CODE = :loantype_code
											and DATEDIFF(month,mb.member_date,getDate()) BETWEEN lc.startmember_time and lc.endmember_time ORDER BY  lc.seq_no DESC");
	$fetchCredit->execute([
		':member_no' => $member_no,
		':loantype_code' => $loantype_code
	]);
	$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
	$member_date_count = $lib->count_duration($rowCredit["MEMBER_DATE"],"m");
	$percentShare = $rowShare["SHARE_STK"];
	$startshare_amt  = $rowCredit["STARTSHARE_AMT"];
	$multiple_share = $rowCredit["MULTIPLE_SHARE"];
	$multiple_salary = $rowCredit["MULTIPLE_SALARY"];
	$percentShare = ($rowShare["SHARE_STK"] * $multiple_share);
	$percentSalary = ($rowCredit["SALARY_AMOUNT"] * $multiple_salary);
	$request_amt = ($percentShare + $percentSalary);
	
	if($member_date_count >= "6" && $member_date_count < "12"){	
		$maxloan_amt = 2000000 ;
	}else if($member_date_count >= "12" && $member_date_count < "18"){
		$maxloan_amt = 300000 ;
	}else if($member_date_count >= "18" && $member_date_count < "24"){
		$maxloan_amt = 500000 ;
	}else if($member_date_count >= "24" && $member_date_count < "42"){
		$maxloan_amt = 600000 ;
	}else if($member_date_count >= "42" && $member_date_count < "60"){
		$maxloan_amt = 700000 ;
	}else if($member_date_count >= "60" && $member_date_count < "72"){
		$maxloan_amt = 800000 ;
	}else if($member_date_count >= "72" && $member_date_count < "84"){
		$maxloan_amt = 900000 ;
	}else if($member_date_count >= "84" && $member_date_count < "108"){
		$maxloan_amt = 1500000 ;
	}else if($member_date_count >= "108" && $member_date_count < "120"){
		$maxloan_amt = 1700000 ;
	}else if($member_date_count >= "120" && $member_date_count < "150"){
		$maxloan_amt = 2000000 ;
	}else if($member_date_count >= "150"){
		$maxloan_amt = 2500000 ;
	}
	
	if($percentShare > 250000){
		$maxloanshare_amt = 2500000;
	}else if($percentShare > 200000){
		$maxloanshare_amt = 2000000;
	}else if($percentShare > 170000){
		$maxloanshare_amt = 1700000;
	}else if($percentShare > 120000){
		$maxloanshare_amt = 1500000;
	}else if($percentShare > 100000){
		$maxloanshare_amt = 1300000;
	}else if($percentShare > 80000){
		$maxloanshare_amt = 1100000;
	}else if($percentShare > 60000){
		$maxloanshare_amt = 900000;
	}else if($percentShare > 50000){
		$maxloanshare_amt = 800000;
	}else if($percentShare > 40000){
		$maxloanshare_amt = 700000;
	}else if($percentShare > 30000){
		$maxloanshare_amt = 600000;
	}else if($percentShare > 20000){
		$maxloanshare_amt = 500000;
	}else if($percentShare > 12000){
		$maxloanshare_amt = 300000;
	}else if($percentShare > 12000){
		$maxloanshare_amt = 200000;
	}
	
	$arrMin[] = $maxloan_amt;
	$arrMin[] = $maxloanshare_amt;
	$maxloan_amt = min($arrMin);
	
	
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "อัตราดอกเบี้ย";
	$arrSubOtherInfo["VALUE"] = number_format($loantint_rate ,2) .' %';
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
	$arrSubOtherInfo["VALUE"] = "250 งวด";
	$arrOtherInfo[] = $arrSubOtherInfo;
}else{
	$maxloan_amt = 0;
	$arrSubOtherInfo = array();
	$arrSubOtherInfo["LABEL"] = "ต้องเป็นสมาชิกมาแล้วไม่ต่ำกว่า 3 เดือน";
	$arrSubOtherInfo["VALUE"] = "";
	$arrOtherInfo[] = $arrSubOtherInfo;
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
}

?>
