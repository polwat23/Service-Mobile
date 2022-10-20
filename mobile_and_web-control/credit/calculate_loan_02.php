<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$receive_net = 0;
$oldConsBal = 0;
$oldGroupBal01 = 0;
$oldGroupBal02 = 0;
$maxloanpermit_amt = 30000;
$sum_old_payment = 0;
$is_cant_refinance = false;
$other_amt = 0;
$cal_period = 0;
$emp_fund_amt = 0;
$is_morethan_funding = false;

$getMemberIno = $conmssql->prepare("SELECT MEMBER_DATE,SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
$getMemberIno->execute([':member_no' => $member_no]);
$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
$duration_month = $lib->count_duration($rowMember["MEMBER_DATE"],'m');
	$getShareBF = $conmssql->prepare("SELECT (SHARESTK_AMT * 10) AS SHARESTK_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
	$getShareBF->execute([':member_no' => $member_no]);
	$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
	$getFundColl = $conmssql->prepare("SELECT SUM(EST_PRICE) as FUND_AMT FROM LNCOLLMASTER WHERE COLLMAST_TYPE = '05' AND MEMBER_NO = :member_no");
	$getFundColl->execute([':member_no' => $member_no]);
	$rowFund = $getFundColl->fetch(PDO::FETCH_ASSOC);
	//ดึงข้อมูลสัญญาเดิม
	$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
										FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
	$getOldContract->execute([
		':member_no' => $member_no
	]);

	while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
		$arrContract = array();
		$contract_no = preg_replace('/\//','',$rowOldContract["LOANCONTRACT_NO"]);
		$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
		$arrContract["CONTRACT_NO"] = $contract_no;
		$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
		$arrContract['BALANCE_AND_INTEREST'] = $rowOldContract["PRINCIPAL_BALANCE"] + $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
		//$arrContract['INTEREST'] = round($cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]), 0, PHP_ROUND_HALF_DOWN);
		$arrContract['INTEREST_AMT'] = $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
		if($rowOldContract["LOANTYPE_CODE"] == $loantype_code){
			$getMoraStatement = $conmssql->prepare("SELECT COUNT(lsm.SEQ_NO) as COUNT_MORA
						FROM lncontstatement lsm LEFT JOIN LNUCFLOANITEMTYPE lit
						ON lsm.LOANITEMTYPE_CODE = lit.LOANITEMTYPE_CODE
						WHERE RTRIM(lsm.loancontract_no) = :loancontract_no and lsm.LOANITEMTYPE_CODE = 'LPM' and lsm.PRINCIPAL_PAYMENT = 0 and lsm.ENTRY_ID != 'CNV'");
			$getMoraStatement->execute([
				':loancontract_no' => $rowOldContract["LOANCONTRACT_NO"],
			]);
			$rowMoraStatement = $getMoraStatement->fetch(PDO::FETCH_ASSOC);
			$contract_period = $rowOldContract["LAST_PERIODPAY"] ?? 0;
			$moratorium_period = $rowMoraStatement["COUNT_MORA"] ?? 0;
			$last_periodpay = $contract_period - $moratorium_period;
			if($last_periodpay <= 2){
				$is_cant_refinance = true;
			}else{
				$arrOldContract[] = $arrContract;
			}
		}
		if(isset($dataComing["old_contract_selected"]) && $dataComing["old_contract_selected"] != ""){
			if(strpos($dataComing["old_contract_selected"], $arrContract["CONTRACT_NO"]) === false){
				$sum_old_payment += $rowOldContract["PERIOD_PAYMENT"];
				$oldConsBal += $rowOldContract["PRINCIPAL_BALANCE"];
			}else{
				$oldConsBal += $rowOldContract["PRINCIPAL_BALANCE"] + $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
			}
		}else{
			$sum_old_payment += $rowOldContract["PERIOD_PAYMENT"];
			$oldConsBal += $rowOldContract["PRINCIPAL_BALANCE"];
		}
		
		if($rowOldContract["LOANGROUP_CODE"] == "01"){
			$oldGroupBal01 += $rowOldContract["PRINCIPAL_BALANCE"];
		}else if($rowOldContract["LOANGROUP_CODE"] == "02"){
			$oldGroupBal02 += $rowOldContract["PRINCIPAL_BALANCE"];
		}
	}
	
	$sum_old_payment += $rowShareBF["PERIOD_SHARE_AMT"];
	
	$arrMin[] = (($rowShareBF["SHARESTK_AMT"] + $rowFund["FUND_AMT"]) - $oldConsBal) + $dataComing["old_contract_balance"];
	//$arrMin[] = $maxloanpermit_amt;
	//$arrMin[] = $rowMember["SALARY_AMOUNT"];
	$emp_fund_amt = ($rowShareBF["SHARESTK_AMT"]);
	
	if($loantype_code == "117" || $loantype_code == "216"){
		$arrMin[] = 50000;
	}
	$maxloan_amt = min($arrMin);

	$getMthOther = $conmssql->prepare("SELECT SUM(mthother_amt) as MTHOTHER_AMT FROM mbmembmthother WHERE member_no = :member_no and sign_flag = '-1'");
	$getMthOther->execute([':member_no' => $member_no]);
	$rowOther = $getMthOther->fetch(PDO::FETCH_ASSOC);
	$other_amt = $rowOther["MTHOTHER_AMT"] ?? 0;
	$sum_old_payment += $other_amt;
	
	/*//$maxloan_amt = intval($maxloan_amt - ($maxloan_amt % 100));
	if(isset($dataComing["period"]) && $dataComing["period"] != ""){
		$cal_period = $dataComing["period"];
	}else{
		$getCalMaxPeriod = $conmssql->prepare("SELECT MAX_PERIOD 
						FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
						WHERE lnd.LOANTYPE_CODE = :loantype_code");
		$getCalMaxPeriod->execute([
			':loantype_code' => $loantype_code
		]);
		$rowCalMaxPeriod = $getCalMaxPeriod->fetch(PDO::FETCH_ASSOC);
		if(isset($rowCalMaxPeriod["MAX_PERIOD"])){
			$cal_period = $rowCalMaxPeriod["MAX_PERIOD"];
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0088";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}
	$percent_max =  59.99 - (($sum_old_payment / ($rowMember["SALARY_AMOUNT"]*0.6) * 60));
	$cal_maxloan_amt = floor((($maxloan_amt * $percent_max)/100) * $cal_period);
	
	if($cal_maxloan_amt < $maxloan_amt){
		$maxloan_amt = $cal_maxloan_amt;
	}
	if(($maxloan_amt / $cal_period) > (($rowMember["SALARY_AMOUNT"]*0.6) - $sum_old_payment)){
		$maxloan_amt = (($rowMember["SALARY_AMOUNT"]*0.6) - $sum_old_payment) * $cal_period;
	}
	
	if(($rowMember["SALARY_AMOUNT"]*0.6) > $maxloan_amt && false){
		$canRequest = FALSE;
		$error_desc = "ไม่สามารถกู้ได้ เนื่องจากท่านมีรายการหักเกิน 60% ของเงินเดือน";
	}else{
		$canRequest = TRUE;
	}
	*/
	if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != ""){
		if($dataComing["request_amt"] > $maxloan_amt){
			$dataComing["request_amt"] = $maxloan_amt;
		}
	}
	$maxloan_amt = floor($maxloan_amt);
	$receive_net = $maxloan_amt;
	$calculate_arr = array();
	$calculate_arr["SHARESTK_AMT"] = $rowShareBF["SHARESTK_AMT"];
	$calculate_arr["FUND_AMT"] = $rowFund["FUND_AMT"];
	$calculate_arr["OLDCONSBAL"] = $oldConsBal;
	$calculate_arr["SALARY_AMOUNT"] = $rowMember["SALARY_AMOUNT"];
	$calculate_arr["OTHER_AMT"] = $other_amt;
	$calculate_arr["SUM_OLD_PAYMENT"] = $sum_old_payment;
	
?>
