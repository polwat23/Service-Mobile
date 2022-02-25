<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$shareColl = null;
$getShareData = $conoracle->prepare("SELECT (sh.sharestk_amt * 50) as SHARE_BALANCE
									FROM shsharemaster sh WHERE sh.member_no = :member_no");
$getShareData->execute([':member_no' => $member_no]);
$rowShareData = $getShareData->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowShareData["SHARE_BALANCE"] * 0.40;
?>