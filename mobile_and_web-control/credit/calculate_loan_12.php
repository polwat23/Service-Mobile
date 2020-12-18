<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$max_period = 1;
$fetchDivAvg = $conoracle->prepare("SELECT (div_amt + avg_amt) AS DIV_AVG
									FROM yrdivmaster WHERE TRIM( div_year ) = ( 
										SELECT MAX( account_year ) AS accyear
										FROM cmaccountyear
										WHERE divavgcls_status =1 ) 
									AND member_no = :member_no");
$fetchDivAvg->execute([
	':member_no' => $member_no
]);
$rowDivAvg = $fetchDivAvg->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = floor(round($rowDivAvg["DIV_AVG"] * 0.70,0 )/100)*100;

$canRequest = TRUE;
?>