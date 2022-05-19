<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 0;
$max_period = 0;
$sharestk_amt  = 0;
$salary_amt  = 0 ;

$getShare = $conoracle->prepare("SELECT  (sharestk_amt * 10) as SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);


$getMemberInfo = $conoracle->prepare("SELECT lc.maxloan_amt,lc.multiple_salary,lc.multiple_share,NVL(mb.salary_amount,15000) as salary_amount,lc.startmember_time,mb.member_date
									FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
									WHERE mb.member_no = :member_no and 
									LT.LOANTYPE_CODE = :loantype_code
									and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
$getMemberInfo->execute([':member_no' => $member_no,
						 ':loantype_code' => $dataComing["loantype_code"] ?? $rowCanCal["loantype_code"]
					]);
$rowMemberInfo = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
$member_date_count = $lib->count_duration($rowMemberInfo["MEMBER_DATE"],"m");
$sharestk_amt  = $rowShare["SHARE_AMT"] * $rowMemberInfo["MULTIPLE_SHARE"];
$salary_amt  = $rowMemberInfo["SALARY_AMOUNT"] * $rowMemberInfo["MULTIPLE_SALARY"];

if($member_date_count >= 3){
	if($salary_amt >  $sharestk_amt ){
		$maxloan_amt =  $sharestk_amt;
	}else{
		$maxloan_amt =  $salary_amt;
	}
}else{
	$maxloan_amt = 0;
}
$arrMin = array();
$arrMin[] = $maxloan_amt;
$arrMin[] = $maxloanpermit_amt;

$getOldLoanBal = $conoracle->prepare("SELECT PRINCIPAL_BALANCE FROM lncontmaster WHERE member_no = :member_no 
										and loantype_code = :loantype_code and contract_status > 0 and contract_status <> 8");
$getOldLoanBal->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowOldLoanBal = $getOldLoanBal->fetch(PDO::FETCH_ASSOC);
$getLoanConstant = $conoracle->prepare("SELECT RDINTDEC_TYPE,RDINTSATANG_TYPE FROM lnloanconstant");
$getLoanConstant->execute();
$rowLoanCont = $getLoanConstant->fetch(PDO::FETCH_ASSOC);
$getDataForCalInt = $conoracle->prepare("SELECT LASTCALINT_DATE,INT_CONTINTRATE,PRINCIPAL_BALANCE,LOANPAYMENT_TYPE
										FROM lncontmaster 
										WHERE loantype_code = :loantype_code and contract_status = 1 and member_no = :member_no");
$getDataForCalInt->execute([
	':loantype_code' => $dataComing["loantype_code"],
	':member_no' => $member_no
]);
$rowDataCalInt = $getDataForCalInt->fetch(PDO::FETCH_ASSOC);
if($rowDataCalInt["LOANPAYMENT_TYPE"] == '1'){
	$daydiff = $lib->count_duration($rowDataCalInt["LASTCALINT_DATE"],'d');
	$interest = (($rowDataCalInt["PRINCIPAL_BALANCE"] * ($rowDataCalInt["INT_CONTINTRATE"] / 100)) * $daydiff) / 365;
}
$dayremainEnd = $lib->count_duration('31/12/'.date('Y'),'d');
if($dayremainEnd < 30){
	$dayremainEnd = 0;
}else{
	$dayremainEnd = $lib->count_duration('31/12/'.date('Y'),'m');
}
$interest = $lib->roundDecimal($interest,$rowLoanCont["RDINTSATANG_TYPE"]);
$oldBal = $rowOldLoanBal["PRINCIPAL_BALANCE"];
$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != ""){
	$receive_net = $dataComing["request_amt"] - $oldBal;	
}else{
	$receive_net = $maxloan_amt - $oldBal;	
}
$canRequest = TRUE;
?>