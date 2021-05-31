<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$receive_net = 0;
$maxloanpermit_amt = 30000;
$getMemberIno = $conmssql->prepare("SELECT MEMBER_DATE,SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
$getMemberIno->execute([':member_no' => $member_no]);
$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
$duration_month = $lib->count_duration($rowMember["MEMBER_DATE"],'m');
$getShareBF = $conmssql->prepare("SELECT (SHARESTK_AMT * 10) AS SHARESTK_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
$getShareBF->execute([':member_no' => $member_no]);
$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
$getFundColl = $conmssql->prepare("SELECT SUM(EST_PRICE) as FUND_AMT FROM LNCOLLMASTER WHERE COLLMAST_TYPE = '05' AND MEMBER_NO = :member_no");
$getFundColl->execute([':member_no' => $member_no]);
$rowFund = $getFundColl->fetch(PDO::FETCH_ASSOC);
$arrMin[] = $rowShareBF["SHARESTK_AMT"] + $rowFund["FUND_AMT"];
$arrMin[] = $maxloanpermit_amt;
$arrMin[] = $rowMember["SALARY_AMOUNT"];
$maxloan_amt = min($arrMin);
$getMthOther = $conmssql->prepare("SELECT SUM(mthother_amt) as MTHOTHER_AMT FROM mbmembmthother WHERE member_no = :member_no and sign_flag = '-1'");
$getMthOther->execute([':member_no' => $member_no]);
$rowOther = $getMthOther->fetch(PDO::FETCH_ASSOC);
$maxloan_amt -= $rowOther["MTHOTHER_AMT"];
if(($rowMember["SALARY_AMOUNT"]*0.6) > $maxloan_amt){
	$canRequest = FALSE;
	$error_desc = "ไม่สามารถกู้ได้ เนื่องจากท่านมีรายการหักเกิน 60% ของเงินเดือน";
}else{
	$canRequest = TRUE;
}
$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
$receive_net = $maxloan_amt;


?>
