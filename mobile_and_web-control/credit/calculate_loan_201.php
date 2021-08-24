<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$receive_net = 0;
$getMemberIno = $conmssql->prepare("SELECT MEMBER_DATE,SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
$getMemberIno->execute([':member_no' => $member_no]);
$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
$duration_month = $lib->count_duration($rowMember["MEMBER_DATE"],'m');
	$getShareBF = $conmssql->prepare("SELECT (SHARESTK_AMT * 10) AS SHARESTK_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
	$getShareBF->execute([':member_no' => $member_no]);
	$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
	$maxloan_amt = $rowShareBF["SHARESTK_AMT"];
	
	//ดึงข้อมูลสัญญาเดิม
	$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY 
										FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
	$getOldContract->execute([
		':member_no' => $member_no,
	]);
	$rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC);
	if(isset($rowOldContract["LOANCONTRACT_NO"]) && $rowOldContract["LOANCONTRACT_NO"] != ""){
		$arrContract = array();
		$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
		$arrContract["CONTRACT_NO"] = $rowOldContract["LOANCONTRACT_NO"];
		$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
		//$oldBal += $rowOldContract["PRINCIPAL_BALANCE"] + $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
		$arrOldContract[] = $arrContract;
		$canRequest = TRUE;
	}else{
		$canRequest = TRUE;
	}
	
	//คำนวนรายการหัก + ขอกู้ต้องไม่เกิน 60% ของเงินเดือน
	$getSumPayBal = $conmssql->prepare("select TOP 1 receive_amt from kptempreceive where member_no = :member_no ORDER BY recv_period DESC");
	$getSumPayBal->execute([
		':member_no' => $member_no
	]);
	$rowSumPayBal = $getSumPayBal->fetch(PDO::FETCH_ASSOC);
	$sumPay = $rowSumPayBal["RECEIVE_AMT"];
	$salaryBal = ($rowMember["SALARY_AMOUNT"]*0.6) - $sumPay;
	if($maxloan_amt > $salaryBal){
		$maxloan_amt = $salaryBal;
	}
	
	$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
	$receive_net = $maxloan_amt;
	
	$canRequest = TRUE;
?>
