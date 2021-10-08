<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 3000000;
$max_period = 264;
$canRequest = FALSE;
$getSalary = $conmssql->prepare("SELECT SALARY_AMOUNT,MEMBER_DATE FROM mbmembmaster WHERE member_no = :member_no");
$getSalary->execute([':member_no' => $member_no]);
$rowSalary = $getSalary->fetch(PDO::FETCH_ASSOC);
$member_age = $lib->count_duration($rowSalary["MEMBER_DATE"],"m");
$rowSalary = $rowSalary["SALARY_AMOUNT"];

if($member_age > 36){
	$maxloan_amt = $rowSalary  * 80;
}else if($member_age > 24){
	$maxloan_amt = $rowSalary  * 70;
}else if($member_age > 15){
	$maxloan_amt = $rowSalary  * 50;
}else if($member_age > 3){
	$maxloan_amt = $rowSalary  * 36;
}
if($maxloan_amt > $maxloanpermit_amt){
	$maxloan_amt = $maxloanpermit_amt;
}


$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY 
									FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
									WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8 
									and lm.loantype_code = :loantype_code");
$getOldContract->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC);
if(isset($rowOldContract["LOANCONTRACT_NO"]) && $rowOldContract["LOANCONTRACT_NO"] != ""){
	if($rowOldContract["LAST_PERIODPAY"] >= 1){
		$arrContract = array();
		$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
		$arrContract["CONTRACT_NO"] = $rowOldContract["LOANCONTRACT_NO"];
		$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
		$oldBal += $rowOldContract["PRINCIPAL_BALANCE"] + $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
		$arrOldContract[] = $arrContract;
		$canRequest = TRUE;
	}else{
		$arrOther = array();
		$arrOther["LABEL"] = "ท่านต้องผ่อนชำระมาแล้วอย่างน้อย 1 งวด";
		$arrOther["LEBEL_TEXT_PROPS"] = ["color" => 'red'];
		$arrOtherInfo[] = $arrOther;
		$canRequest = FALSE;
		$maxloan_amt = 0;
	}
}else{
	$canRequest = TRUE;
}
$receive_net = $maxloan_amt - $oldBal;
?>