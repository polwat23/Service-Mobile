<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$receive_net = 0;
$oldBal = 0;
$request_amt = 0;
$cal_remark = null;

$getShareStk = $conoracle->prepare("SELECT (SHARESTK_AMT * 10) AS SHARESTK_AMT FROM SHSHAREMASTER WHERE MEMBER_NO = :member_no");
$getShareStk->execute([':member_no' => $member_no]);
$rowShareStk = $getShareStk->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowShareStk["SHARESTK_AMT"] * 0.95;
$getLoanOld2H = $conoracle->prepare("SELECT PRINCIPAL_BALANCE
								FROM LNCONTMASTER
								WHERE LOANTYPE_CODE = '2H' AND MEMBER_NO = :member_no 
								AND CONTRACT_STATUS > '0' AND CONTRACT_STATUS <> '8'");
$getLoanOld2H->execute([':member_no' => $member_no]);
$rowLoan2H = $getLoanOld2H->fetch();
$maxloan_amt -= $rowLoan2H["PRINCIPAL_BALANCE"];
if($maxloan_amt > 150000){
	$maxloan_amt = 150000;
}
$getLoanOld = $conoracle->prepare("SELECT LN.PRINCIPAL_BALANCE,LN.LOANCONTRACT_NO,LN.LAST_PERIODPAY,LN.LOANAPPROVE_AMT,LT.LOANTYPE_DESC
								FROM LNCONTMASTER LN LEFT JOIN LNLOANTYPE LT ON LN.LOANTYPE_CODE = LT.LOANTYPE_CODE
								WHERE LN.LOANTYPE_CODE IN('2J','10','11','12','13','14','15','16') AND LN.MEMBER_NO = :member_no 
								AND LN.CONTRACT_STATUS > '0' AND LN.CONTRACT_STATUS <> '8'");
$getLoanOld->execute([':member_no' => $member_no]);
while($rowLoanold = $getLoanOld->fetch(PDO::FETCH_ASSOC)){
	$interest = $cal_loan->calculateInterest($rowLoanold["LOANCONTRACT_NO"]);
	$oldBal += $rowLoanold["PRINCIPAL_BALANCE"] + $interest;
	$arrContract = array();
	$arrContract["LOANTYPE_DESC"] = $rowLoanold["LOANTYPE_DESC"];
	$arrContract["BALANCE"] = $rowLoanold["PRINCIPAL_BALANCE"] + $interest;
	$arrOldContract[] = $arrContract;
}
$getLoanOld2J = $conoracle->prepare("SELECT LN.PRINCIPAL_BALANCE,LN.LOANCONTRACT_NO,LN.LAST_PERIODPAY,LN.LOANAPPROVE_AMT,LT.LOANTYPE_DESC
								FROM LNCONTMASTER LN LEFT JOIN LNLOANTYPE LT ON LN.LOANTYPE_CODE = LT.LOANTYPE_CODE
								WHERE LN.LOANTYPE_CODE IN('2J') AND LN.MEMBER_NO = :member_no 
								AND LN.CONTRACT_STATUS > '0' AND LN.CONTRACT_STATUS <> '8'");
$getLoanOld2J->execute([':member_no' => $member_no]);
$rowLoanold2J = $getLoanOld2J->fetch(PDO::FETCH_ASSOC);
if(isset($rowLoanold2J["LOANTYPE_DESC"]) && $rowLoanold2J["LOANTYPE_DESC"] != ""){
	if($rowLoanold2J["LAST_PERIODPAY"] < 6 && ($rowLoanold2J["LOANAPPROVE_AMT"]  - ($rowLoanold2J["LOANAPPROVE_AMT"] * 0.10)) < $rowLoanold2J["PRINCIPAL_BALANCE"]){
		$maxloan_amt = 0;
		$cal_remark = "ยื่นขอกู้อีกครั้งได้ก็ต่อเมื่อท่านผ่อนชำระหนี้เดิมมาแล้ว 6 เดือนขึ้นไป หรือผ่อนชำระหนี้แล้วไม่น้อยกว่าร้อยละ 10 ของจำนวนเงินกู้";
	}
}
$maxloan_amt = $dataComing["request_amt"] ?? $maxloan_amt;
if($oldBal > $maxloan_amt){
	$maxloan_amt = 0;
	$cal_remark = "หนี้เดิมของท่านมากกว่าสิทธิ์กู้ ท่านไม่สามารถขอกู้ได้ ณ ขณะนี้";
}else{
	$canRequest = true;
	$receive_net = $maxloan_amt - $oldBal;
}
?>