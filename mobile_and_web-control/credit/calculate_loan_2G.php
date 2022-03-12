<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrAccType = array();
$balanceAll = 0;
/*
if($arrResponseAPI->responseCode == "200"){
	foreach($arrResponseAPI->accountDetail as $accData){
		if($accData->accountType != '40' && $accData->accountStatus == "0"){
			$balanceAll += preg_replace('/,/', '', $accData->accountBalance);
		}
		$arrAccType[] = (string)$accData->accountType;
	}
}*/
if(in_array("10", $arrAccType) || in_array("11", $arrAccType) || in_array("16", $arrAccType) || in_array("17", $arrAccType)
|| in_array("13", $arrAccType) || in_array("21", $arrAccType)){
	$getPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM LNLOANTYPEPERIOD 
									WHERE LOANTYPE_CODE = :loantype_code");
	$getPeriod->execute([':loantype_code' => $loantype_code]);
	$rowPeriod = $getPeriod->fetch(PDO::FETCH_ASSOC);
	$arrSubOther["LABEL"] = "งวดสูงสุด";
	$max_period = $rowPeriod["MAX_PERIOD"];
	$maxloan_amt = $balanceAll * 0.95;
	$arrSubOther["VALUE"] = $max_period." งวด";
	$arrOtherInfo[] = $arrSubOther;
	$arrSubOther["LABEL"] = "อายัติยอดเงินเพื่อหลักประกัน";
	$arrSubOther["VALUE"] = number_format($maxloan_amt * 0.95,2)." บาท";
	$arrOtherInfo[] = $arrSubOther;
	$arrSubOther["LABEL"] = "ผ่อนชำระขั้นต่ำงวดละ";
	$arrSubOther["VALUE"] = "1,000.00 บาท";
	$arrOtherInfo[] = $arrSubOther;
}else{
	$maxloan_amt = 0;
	$arrSubOther["LABEL"] = "ต้องมี บัญชีเงินฝากออมทรัพย์ หรือ บัญชีเงินฝากออมทรัพย์พิเศษ 1, 2, 3 หรือ บัญชีเงินฝากออมทรัพย์พิเศษเกษียณสุข หรือ บัญชีเงินฝากประจำ";
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
	$arrCollShould[] = $arrSubOther;
}
$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
?>