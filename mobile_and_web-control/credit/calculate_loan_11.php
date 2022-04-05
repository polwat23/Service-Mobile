<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 0;
$sharestk_amt  = 0;

$fetchmaxloan = $conoracle->prepare("SELECT maxloan_amt FROM lnloantype WHERE loantype_code = :loantype_code");
$fetchmaxloan->execute([':loantype_code' => $dataComing["loantype_code"] ?? $rowCanCal["loantype_code"]]);
$rowMaxloan = $fetchmaxloan->fetch(PDO::FETCH_ASSOC);
$maxloanpermit_amt = $rowMaxloan["MAXLOAN_AMT"];

$getShare = $conoracle->prepare("SELECT  (sharestk_amt * 10) as SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);
$sharestk_amt = $rowShare["SHARE_AMT"];


$getMemberInfo = $conoracle->prepare("SELECT SALARY_AMOUNT ,MEMBER_DATE FROM mbmembmaster WHERE member_no = :member_no");
$getMemberInfo->execute([':member_no' => $member_no]);
$rowMemberInfo = $getMemberInfo->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowMemberInfo["SALARY_AMOUNT"] * 3;
$member_date_count = $lib->count_duration($rowMemberInfo["MEMBER_DATE"],"m");

if($member_date_count < 3){
	$maxloan_amt = 0;	
}else{
	if($maxloanpermit_amt > $sharestk_amt){
		$maxloanpermit_amt = $sharestk_amt;
		if($maxloan_amt > $maxloanpermit_amt){
			$maxloan_amt = $maxloanpermit_amt;
		}
	}else{
		if($maxloan_amt > $maxloanpermit_amt){
			$maxloan_amt = $maxloanpermit_amt;
		}
	}
}

$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
$receive_net = $maxloan_amt;
$canRequest = TRUE;
?>