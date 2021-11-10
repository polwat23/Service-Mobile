<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$period = 240;
$getShare = $conoracle->prepare("SELECT (sharestk_amt*10) as SHARE_VALUE FROM shsharemaster WHERE member_no = :member_no");
$getShare->execute([':member_no' => $member_no]);
$rowShare = $getShare->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowShare["SHARE_VALUE"] * 0.90;
$maxloan_amt = $maxloan_amt - ($maxloan_amt % 100);
$canRequest = TRUE;
$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
$arrSubOtherInfo["VALUE"] = "240 งวด";
$arrOtherInfo[] = $arrSubOtherInfo;
$checkLoanBan = $conoracle->prepare("SELECT LT.LOANPERMGRP_CODE, LN.LOANTYPE_CODE FROM LNCONTMASTER LN LEFT JOIN LNLOANTYPE LT ON LN.LOANTYPE_CODE = LT.LOANTYPE_CODE
									WHERE LN.MEMBER_NO = :member_no AND LN.CONTRACT_STATUS > 0 AND LN.CONTRACT_STATUS <> 8");
$checkLoanBan->execute([':member_no' => $member_no]);
while($rowLoan = $checkLoanBan->fetch(PDO::FETCH_ASSOC)){
	$arrayOther = array();
	if(($rowLoan["LOANPERMGRP_CODE"] == '02' || $rowLoan["LOANPERMGRP_CODE"] == '03') && $rowLoan["LOANTYPE_CODE"] != '25'){
		$arrayOther["LABEL"] = "ไม่สามารถขอกู้ได้ เนื่องจากท่านมีหนี้สามัญหรือพิเศษ";
		$arrayOther["LEBEL_TEXT_PROPS"] = ["color" => "red"];
		$arrOtherInfo[] = $arrayOther;
		$canRequest = FALSE;
		break;
	}
}
$getOldLoan = $conoracle->prepare("SELECT lm.PRINCIPAL_BALANCE,lt.loantype_desc,lm.LOANCONTRACT_NO
								FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
								WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8 and lm.loantype_code IN('25')");
$getOldLoan->execute([':member_no' => $member_no]);
while($rowOldLoan = $getOldLoan->fetch(PDO::FETCH_ASSOC)){
	$arrOld = array();
	$arrOld["BALANCE"] = $rowOldLoan["PRINCIPAL_BALANCE"];
	$arrOld["LOANTYPE_DESC"] = $rowOldLoan["LOANTYPE_DESC"];
	$arrOld["CONTRACT_NO"] = $rowOldLoan["LOANCONTRACT_NO"];
	$oldBal += $rowOldLoan["PRINCIPAL_BALANCE"];
	$arrOldContract[] = $arrOld;
}
if($oldBal > $maxloan_amt){
	$arrayOther["LABEL"] = "ไม่สามารถขอกู้ได้ เนื่องจากหนี้เดิมของท่านมากกว่าสิทธิ์กู้";
	$arrayOther["LEBEL_TEXT_PROPS"] = ["color" => "red"];
	$arrOtherInfo[] = $arrayOther;
	$canRequest = FALSE;
}
?>