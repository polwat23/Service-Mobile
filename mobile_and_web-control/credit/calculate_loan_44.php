<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 0;

$getMemb = $conoracle->prepare("SELECT mb.MEMBTYPE_CODE,mg.MEMBGROUP_CONTROL,(sh.sharestk_amt*10) as SHARE_AMT,mb.SALARY_AMOUNT
											FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no 
											LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
											WHERE mb.member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);

$getMemberInfo = $conoracle->prepare("SELECT BIRTH_DATE, MEMBER_DATE, SALARY_AMOUNT FROM MBMEMBMASTER WHERE MEMBER_NO = :member_no");
$getMemberInfo->execute([':member_no' => $member_no]);
$rowMemberInfo = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
$max_salary = $rowMemberInfo["SALARY_AMOUNT"] * 60;
$count_bdate_year = $lib->count_duration($rowMemberInfo["BIRTH_DATE"],"m")/12;
$member_date_count = $lib->count_duration($rowMemberInfo["MEMBER_DATE"],"m");
$typeMember = substr($member_no,2,1);

$arrMin = array();
$arrMin[] = $rowMemb["SHARE_AMT"] * 0.9;
$maxloan_amt = min($arrMin);

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
$receive_net = $maxloan_amt - $oldBal;
$canRequest = TRUE;
?>