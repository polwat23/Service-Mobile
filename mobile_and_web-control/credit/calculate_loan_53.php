<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$getLoantypeCustom = $conoracle->prepare("SELECT l.MAXLOAN_AMT,l.MULTIPLE_SALARY,NVL(m.SALARY_AMOUNT,15000) as SALARY_AMOUNT,s.SHARESTK_AMT * 10 as SHARESTK_VALUE FROM lnloantypecustom l,mbmembmaster m 
										LEFT JOIN shsharemaster s ON m.member_no = s.member_no
										where l.loantype_code = '53' 
										AND TRUNC(MONTHS_BETWEEN (SYSDATE,m.member_date ) /12 *12) between
										l.startmember_time and l.endmember_time
										and m.member_no = :member_no");
$getLoantypeCustom->execute([':member_no' => $member_no]);
$rowCustom = $getLoantypeCustom->fetch(PDO::FETCH_ASSOC);
$percentSalary = $rowCustom['SALARY_AMOUNT'] * $rowCustom["MULTIPLE_SALARY"];
$maxloan_amt = min($percentSalary,$rowCustom["MAXLOAN_AMT"]);
$percentShare = $rowCustom['SHARESTK_VALUE'] * 0.30;
$valueNormalRate = $maxloan_amt * 0.30;
$valueNormalRate = $valueNormalRate - $percentShare;
$maxloan_amt -= $valueNormalRate;
$getOldContract = $conoracle->prepare("SELECT lm.principal_balance,lt.loantype_desc,lm.loancontract_no FROM lncontmaster lm 
										LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.loantype_code IN('53','58')");
$getOldContract->execute([
	':member_no' => $member_no
]);
while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
	$arrContract = array();
	$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
	$arrContract["CONTRACT_NO"] = $rowOldContract["LOANCONTRACT_NO"];
	$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
	$oldBal += $rowOldContract["PRINCIPAL_BALANCE"];
	$arrOldContract[] = $arrContract;
}
$arrCredit["OLD_CONTRACT"] = $arrOldContract;

if($maxloan_amt < 0){
	$maxloan_amt = 0;
}
$arrSubOtherInfo["LABEL"] = "กู้ได้ไม่เกิน";
$getPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM lnloantypeperiod WHERE loantype_code = :loantype_code and money_from <= :loanamt and money_to >= :loanamt");
$getPeriod->execute([
	':loantype_code' => $loantype_code,
	':loanamt' => $maxloan_amt
]);
$rowPeriod = $getPeriod->fetch(PDO::FETCH_ASSOC);
$arrSubOtherInfo["VALUE"] = $rowPeriod["MAX_PERIOD"]." งวด";
/*if($loantype_code == '55'){
	if($maxloan_amt > 3000000){
		$maxloan_amt = 3000000;
	}
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}else if($loantype_code == '10'){
	$arrSubOtherInfo["VALUE"] = "10 งวด";
}else if($loantype_code == '21'){
	$arrSubOtherInfo["VALUE"] = "10 งวด";
}else if($loantype_code == '22'){
	$arrSubOtherInfo["VALUE"] = "10 งวด";
}else if($loantype_code == '31'){
	$arrSubOtherInfo["VALUE"] = "250 งวด";
}else if($loantype_code == '38'){
	$arrSubOtherInfo["VALUE"] = "360 งวด";
}else if($loantype_code == '41'){
	$arrSubOtherInfo["VALUE"] = "200 งวด";
}else if($loantype_code == '42'){
	$arrSubOtherInfo["VALUE"] = "360 งวด";
}else if($loantype_code == '12'){
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}else if($loantype_code == '52'){
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}else if($loantype_code == '53'){
	$arrSubOtherInfo["VALUE"] = "48 งวด";
}else if($loantype_code == '13'){
	$arrSubOtherInfo["VALUE"] = "12 งวด";
}else if($loantype_code == '14'){
	$arrSubOtherInfo["VALUE"] = "36 งวด";
}else if($loantype_code == '15'){
	$arrSubOtherInfo["VALUE"] = "24 งวด";
}else if($loantype_code == '17'){
	$arrSubOtherInfo["VALUE"] = "36 งวด";
}else if($loantype_code == '57'){
	$arrSubOtherInfo["VALUE"] = "12 งวด";
}else if($loantype_code == '58'){
	$arrSubOtherInfo["VALUE"] = "24 งวด";
}else{
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}*/
$arrOtherInfo[] = $arrSubOtherInfo;
?>