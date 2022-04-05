<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 0;
$sharestk_amt  = 0;
$salary_amt  = 0 ;
$loan_permit = 0;

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
$salary_amt = $rowMemberInfo["SALARY_AMOUNT"] * $rowMemberInfo["MULTIPLE_SALARY"];
$sharestk_amt = $rowShare["SHARE_AMT"] * $rowMemberInfo["MULTIPLE_SHARE"];

if($salary_amt  > $sharestk_amt){
	$loan_permit = $sharestk_amt;
}else{
	$loan_permit = $salary_amt;
}

if($member_date_count < 12){
	$maxloan_amt = 0;	
}else{
	if($loan_permit > $rowMemberInfo["MAXLOAN_AMT"]){
		$maxloan_amt = $rowMemberInfo["MAXLOAN_AMT"];
	}else{
		$maxloan_amt = $loan_permit ;
	}	
}
$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
$receive_net = $maxloan_amt;
$canRequest = TRUE;
?>