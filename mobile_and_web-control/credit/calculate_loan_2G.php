<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrAccType = array();
$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
$arrDataAPI["MemberID"] = substr($member_no,-6);
$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
$arrResponseAPI = json_decode($arrResponseAPI);
if($arrResponseAPI->responseCode == "200"){
	foreach($arrResponseAPI->accountDetail as $accData){
		$arrAccType[] = (string)$accData->accountType;
	}
}
if(in_array("10", $arrAccType) || in_array("11", $arrAccType) || in_array("16", $arrAccType) || in_array("17", $arrAccType)
|| in_array("13", $arrAccType) || in_array("21", $arrAccType)){
	$getPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM LNLOANTYPEPERIOD 
									WHERE LOANTYPE_CODE = :loantype_code");
	$getPeriod->execute([':loantype_code' => $loantype_code]);
	$rowPeriod = $getPeriod->fetch(PDO::FETCH_ASSOC);
	$arrSubOther["LABEL"] = "งวดสูงสุด";
	$max_period = $rowMemb["REMAIN_PERIOD"];
	if($max_period > $rowPeriod["MAX_PERIOD"]){
		$max_period = $rowPeriod["MAX_PERIOD"];
	}
	$arrSubOther["VALUE"] = $max_period." งวด";
	$arrOtherInfo[] = $arrSubOther;
}else{
	$maxloan_amt = 0;
	$arrSubOther["LABEL"] = "ต้องมี บัญชีเงินฝากออมทรัพย์ หรือ บัญชีเงินฝากออมทรัพย์พิเศษ 1, 2, 3 หรือ บัญชีเงินฝากออมทรัพย์พิเศษเกษียณสุข หรือ บัญชีเงินฝากประจำ";
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
	$arrCollShould[] = $arrSubOther;
}
?>