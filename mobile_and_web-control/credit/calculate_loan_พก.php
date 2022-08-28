<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$maxloanpermit_amt = 500000;
$max_period = 119;
$canRequest = FALSE;
$getSalary = $conmssql->prepare("SELECT mb.membcat_code,mb.membtype_code,mg.MEMBGROUP_CONTROL,(sh.sharestk_amt*10) as SHARE_AMT,mb.SALARY_AMOUNT
											FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no 
											LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
											WHERE mb.member_no = :member_no");
$getSalary->execute([':member_no' => $member_no]);
$rowSalary = $getSalary->fetch(PDO::FETCH_ASSOC);
if($maxloan_amt > $maxloanpermit_amt){
	$maxloan_amt = $maxloanpermit_amt;
}
$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY ,  lt.maxloan_amt
									FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
									WHERE lm.member_no = :member_no 
									and lm.contract_status > 0 
									and lm.contract_status <> 8 
									and lm.loantype_code = :loantype_code");
$getOldContract->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowOldContract["maxloan_amt"] ;
if(isset($rowOldContract["LOANCONTRACT_NO"]) && $rowOldContract["LOANCONTRACT_NO"] != ""){
	$arrContract = array();
	$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
	$arrContract["CONTRACT_NO"] = $rowOldContract["LOANCONTRACT_NO"];
	$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
	$oldBal += $rowOldContract["PRINCIPAL_BALANCE"] ;
	$arrOldContract[] = $arrContract;
	$canRequest = TRUE;
}else{
	$canRequest = TRUE;
}
$receive_net = $maxloan_amt - $oldBal;
?>
