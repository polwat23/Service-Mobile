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

$getShare = $conoracle->prepare("SELECT  (sharestk_amt * 10) as SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);

$getMemberInfo = $conoracle->prepare("SELECT lt.maxloan_amt,NVL(mb.salary_amount,15000) as salary_amount,mb.member_date
									  FROM lnloantype lt ,mbmembmaster mb
									  WHERE mb.member_no = :member_no and  lt.LOANTYPE_CODE = :loantype_code");
$getMemberInfo->execute([':member_no' => $member_no,
						 ':loantype_code' => $dataComing["loantype_code"] ?? $rowCanCal["loantype_code"]
					]);
$rowMemberInfo = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
$member_date_count = $lib->count_duration($rowMemberInfo["MEMBER_DATE"],"m");
$sharestk_amt = $rowShare["SHARE_AMT"] * $rowMemberInfo["MULTIPLE_SHARE"];

if($member_date_count < 2){
	$maxloan_amt = 0;	
}else{
	$maxloan_amt = $rowMemberInfo["MAXLOAN_AMT"] ;
}
$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
$receive_net = $maxloan_amt;
$canRequest = TRUE;
?>