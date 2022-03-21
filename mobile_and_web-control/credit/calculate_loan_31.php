<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrOldContract = array();
$maxloan_amt = 8000000;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "อัตราดอกเบี้ย";
$arrSubOtherInfo["VALUE"] = "5 %";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "งวดสูงสุด";
$arrSubOtherInfo["VALUE"] = "360 งวด";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo = array();
$arrSubOtherInfo["LABEL"] = "ทั้งนี้สิทธิกู้ขึ้นอยู่กับหลักทรัพย์ค้ำประกันแต่ไม่เกิน";
$arrSubOtherInfo["VALUE"] = "2,000,000 บาท";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
?>