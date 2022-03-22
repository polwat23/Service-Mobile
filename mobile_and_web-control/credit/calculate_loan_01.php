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
$other_amt = 0;
$cal_period = 0;
$getMemberIno = $conmssql->prepare("SELECT MEMBER_DATE,SALARY_AMOUNT,BIRTH_DATE,MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
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
	$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"] + $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
	$arrContract['BALANCE_AND_INTEREST'] = $rowOldContract["PRINCIPAL_BALANCE"] + $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
	$arrContract['INTEREST_AMT'] = $cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]);
	if($rowOldContract["LOANTYPE_CODE"] == $loantype_code){
		$arrOldContract[] = $arrContract;
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
		
$arrMin[] = ($rowShareBF["SHARESTK_AMT"] + $rowFund["FUND_AMT"]) - $oldConsBal;
if(date("Y-m-d") >= '2022-03-10'){
	//สิทธิ์กู้ใหม่
	$arrMin[] = $rowMember["SALARY_AMOUNT"]*2;
}else{
	//สิทธิ์กู้เดิม
	$arrMin[] = $maxloanpermit_amt;
	$arrMin[] = $rowMember["SALARY_AMOUNT"];
}
$maxloan_amt = min($arrMin) + $dataComing["old_contract_balance"];

$getMthOther = $conmssql->prepare("SELECT SUM(mthother_amt) as MTHOTHER_AMT FROM mbmembmthother WHERE member_no = :member_no and sign_flag = '-1'");
$getMthOther->execute([':member_no' => $member_no]);
$rowOther = $getMthOther->fetch(PDO::FETCH_ASSOC);
$other_amt = $rowOther["MTHOTHER_AMT"] ?? 0;
$sum_old_payment += $other_amt;


if(isset($dataComing["period"]) && $dataComing["period"] != ""){
        $cal_period = $dataComing["period"];
}else{
	//เช็ควันเกษียณ สิ้นปี ปีที่ครบ 55 ปี
	$m_birthdate = date('m',strtotime($rowMember["BIRTH_DATE"]));
	$y_birthdate = date('Y')-date('Y',strtotime($rowMember["BIRTH_DATE"]));
	if($rowMember["MEMBGROUP_CODE"] == "SKCM" || $rowMember["MEMBGROUP_CODE"] == "SKLM"){
		$max_member_period = ((60 - $y_birthdate)*12) + (12 - date('m'));
	}else{
		$max_member_period = ((55 - $y_birthdate)*12) + (12 - date('m'));
	}

	$getCalMaxPeriod = $conmssql->prepare("SELECT MAX_PERIOD 
					FROM lnloantype lnt LEFT JOIN lnloantypeperiod lnd ON lnt.LOANTYPE_CODE = lnd.LOANTYPE_CODE
					WHERE lnd.LOANTYPE_CODE = :loantype_code");
	$getCalMaxPeriod->execute([
		':loantype_code' => $loantype_code
	]);
	$rowCalMaxPeriod = $getCalMaxPeriod->fetch(PDO::FETCH_ASSOC);
	if(isset($rowCalMaxPeriod["MAX_PERIOD"])){
		$cal_period = $rowCalMaxPeriod["MAX_PERIOD"];
		//เช็คงวดเกษียณ
		$arrMinPeriod = array();
		$arrMinPeriod[] = $max_member_period;
		$arrMinPeriod[] = $cal_period;
		$cal_period =  min($arrMinPeriod);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0088";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
}
$percent_max =  59.99 - (($sum_old_payment / ($rowMember["SALARY_AMOUNT"]*0.6) * 60));
$cal_maxloan_amt = floor((($maxloan_amt * $percent_max)/100) * $cal_period);
	
$temp_period_payment = (($rowMember["SALARY_AMOUNT"]*0.6) - $sum_old_payment);

//เช็คว่าชำระต่องวดเกินไหม ถ้าไม่เกินให้ปรับสิทธิ์กู้สูงสุด
if((($temp_period_payment) < ($rowMember["SALARY_AMOUNT"]*0.6)) && $cal_maxloan_amt < $maxloan_amt){
	if(($temp_period_payment * $cal_period) < $maxloan_amt){
		$maxloan_amt = $temp_period_payment * $cal_period;
	}else{
		$maxloan_amt = $maxloan_amt;
	}
}else if($cal_maxloan_amt < $maxloan_amt){
	$maxloan_amt = $cal_maxloan_amt;
}

if(($rowMember["SALARY_AMOUNT"]*0.6) > $maxloan_amt && false){
	$canRequest = FALSE;
	$error_desc = "ไม่สามารถกู้ได้ เนื่องจากท่านมีรายการหักเกิน 60% ของเงินเดือน";
}else{
	$canRequest = TRUE;
}

if(isset($dataComing["request_amt"]) && $dataComing["request_amt"] != ""){
	if($dataComing["request_amt"] > $maxloan_amt){
		$dataComing["request_amt"] = $maxloan_amt;
	}
}

$receive_net = $maxloan_amt;
$calculate_arr = array();
$calculate_arr["SHARESTK_AMT"] = $rowShareBF["SHARESTK_AMT"];
$calculate_arr["FUND_AMT"] = $rowFund["FUND_AMT"];
$calculate_arr["OLDCONSBAL"] = $oldConsBal;
$calculate_arr["SALARY_AMOUNT"] = $rowMember["SALARY_AMOUNT"];
$calculate_arr["OTHER_AMT"] = $other_amt;
$calculate_arr["SUM_OLD_PAYMENT"] = $sum_old_payment;
?>
