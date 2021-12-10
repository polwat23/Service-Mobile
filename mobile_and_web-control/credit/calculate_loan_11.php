<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$fetchCredit = $conoracle->prepare("SELECT lc.MAXLOAN_AMT,lc.MULTIPLE_SALARY,TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) as MEMBER_AGE,mb.SALARY_AMOUNT
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
											WHERE mb.member_no = :member_no and 
											LT.LOANTYPE_CODE = :loantype_code
											and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$getShare = $conoracle->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT FROM shsharemaster where member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowCredit["SALARY_AMOUNT"] * $rowCredit["MULTIPLE_SALARY"];
if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
}

$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "งวด";
$arrSubOtherInfo["VALUE"] = "220";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "ต้องมีหุ้นอย่างน้อย 15% ของยอดที่จะกู้";
$arrOtherInfo[] = $arrSubOtherInfo;

?>