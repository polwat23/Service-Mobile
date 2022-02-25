<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$memb_duration = $memb_duration ?? 0;
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$shareColl = null;
if($memb_duration >= 12 && $memb_duration < 36){
	$maxloan_amt = 2000000;
}else if($memb_duration >= 36 && $memb_duration < 48){
	$maxloan_amt = 3000000;
}else if($memb_duration >= 48 && $memb_duration < 60){
	$maxloan_amt = 4000000;
}else if($memb_duration >= 60){
	$maxloan_amt = 5000000;
}
?>