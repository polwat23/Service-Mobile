<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupCredit = array();
		$arrayLoantype = array();
		$getLoantypeCredit = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_creditloan = '1'");
		$getLoantypeCredit->execute();
		while($rowLoanType = $getLoantypeCredit->fetch(PDO::FETCH_ASSOC)){
			$arrayLoantype[] = "'".$rowLoanType["loantype_code"]."'";
		}
		if(isset($dataComing["salary"]) && $dataComing["salary"] > 0){
			$fetchCredit = $conoracle->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt as MAXLOAN_AMT,LT.loantype_code as LOANTYPE_CODE,
												(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (NVL(".$dataComing["salary"].",15000)*lc.multiple_salary ) AS CREDIT_AMT
												FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,
												shsharemaster sm LEFT JOIN mbmembmaster mb ON sm.member_no = mb.member_no,shsharetype sh
												WHERE mb.member_no = :member_no AND sm.SHAREMASTER_STATUS = '1' AND LT.LOANGROUP_CODE IN ( '01','02' )
												AND LT.LOANTYPE_CODE IN (".implode(",",$arrayLoantype).")
												AND TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time
												AND sm.sharestk_amt*sh.unitshare_value BETWEEN lc.startshare_amt AND lc.endshare_amt
												AND NVL(".$dataComing["salary"].",15000) BETWEEN lc.startsalary_amt AND lc.endsalary_amt
												GROUP BY LT.loantype_code,lt.loantype_desc,lc.maxloan_amt,(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (NVL(".$dataComing["salary"].",15000)*lc.multiple_salary)");
			$fetchCredit->execute([':member_no' => $member_no]);
			while($rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC)){
				$arrCredit = array();
				if($rowCredit["CREDIT_AMT"] > $rowCredit["MAXLOAN_AMT"]){
					$loan_amt = $rowCredit["MAXLOAN_AMT"];
				}else{
					$loan_amt = $rowCredit["CREDIT_AMT"];
				}
				$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM LNLOANTYPEPERIOD WHERE LOANTYPE_CODE = ? and money_from <= ? and money_to >= ?");
				$getMaxPeriod->execute([$rowCredit["LOANTYPE_CODE"],$loan_amt,$loan_amt]);
				$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
				$arrCredit["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
				$getIntRate = $conoracle->prepare("SELECT LIR.INTEREST_RATE AS INTEREST_RATE
																	FROM LNLOANTYPE LP LEFT JOIN LNCFLOANINTRATEDET LIR
																	ON LP.INTTABRATE_CODE = LIR.LOANINTRATE_CODE WHERE SYSDATE 
																	BETWEEN TO_CHAR(LIR.EFFECTIVE_DATE, 'YYYYMMDD') AND TO_CHAR(LIR.EXPIRE_DATE, 'YYYYMMDD') AND LP.LOANTYPE_CODE = :loantype_code");
				$getIntRate->execute([':loantype_code' => $rowCredit["LOANTYPE_CODE"]]);
				$rowIntRate = $getIntRate->fetch(PDO::FETCH_ASSOC);
				$int_rate = $rowIntRate["INTEREST_RATE"] / 100;
				if($loan_amt % 1000 > 0){
					$loan_amt = floor($loan_amt - ($loan_amt % 1000));
				}
				$intCal = (($loan_amt * $int_rate) * 31) / 365;
				$interest = $lib->roundDecimal($intCal,1);
				$actualCredit = $loan_amt;
				$pay_period = $loan_amt / $rowMaxPeriod["MAX_PERIOD"];
				if($pay_period % 100 > 0){
					$pay_period = floor($pay_period + (100 - ($pay_period % 100)));
				}
				$intCal = (($loan_amt * $int_rate) * 31) / 365;
				$remainSalary = $dataComing["salary"] - $pay_period - $interest - $dataComing["other_exp"];
				if($remainSalary < 0){
					$remainSalary = 0;
				}
				$getSalaryBal = $conoracle->prepare("SELECT  LCB.SALARYBAL_PERCENT
																	FROM LNLOANTYPE LN
																	LEFT OUTER JOIN LNCFSALARYBALANCE LCB ON LN.SALARYBAL_CODE = LCB.SALARYBAL_CODE
																	WHERE LN.LOANTYPE_CODE = :loantype_code");
				$getSalaryBal->execute([':loantype_code' => $rowCredit["LOANTYPE_CODE"]]);
				$rowSalaryPercent = $getSalaryBal->fetch(PDO::FETCH_ASSOC);
				if(isset($rowSalaryPercent["SALARYBAL_PERCENT"]) && $rowSalaryPercent["SALARYBAL_PERCENT"] != ""){
					$salaryActual = $dataComing["salary"] * ($rowSalaryPercent["SALARYBAL_PERCENT"] / 100);
					if($remainSalary < $salaryActual){
						$salaryCanLoan = $dataComing["salary"] -  $dataComing["other_exp"] - $salaryActual;
						if($loan_amt  % 1000 > 0){
							$actualCredit = floor($loan_amt - ($loan_amt % 1000));
						}
						$salaryCanLoan -= (($actualCredit * $int_rate) * 31) / 365;
						if($salaryCanLoan < 0){
							$salaryCanLoan = 0;
						}
						$loan_amt = $salaryCanLoan * $rowMaxPeriod["MAX_PERIOD"];
						if($loan_amt % 1000 > 0){
							$loan_amt = floor($loan_amt - ($loan_amt % 1000));
						}
						$intCal = (($loan_amt * $int_rate) * 31) / 365;
						$interest = $lib->roundDecimal($intCal,1);
						$pay_period = $loan_amt / $rowMaxPeriod["MAX_PERIOD"];
						if($pay_period % 100 > 0){
							$pay_period = floor($pay_period + (100 - ($pay_period % 100)));
						}
						$remainSalary = $dataComing["salary"] - $pay_period - $interest - $dataComing["other_exp"];
					}
				
				}
				
				$arrOther = array();
				$arrOther[0]["LABEL"] = "ชำระต้นต่องวด";
				$arrOther[0]["VALUE"] = number_format($pay_period,2);
				$arrOther[1]["LABEL"] = "ประมาณการดอกเบี้ย";
				$arrOther[1]["VALUE"] = number_format($interest,2);
				$arrOther[2]["LABEL"] = "เงินเดือนคงเหลือ";
				$arrOther[2]["VALUE"] = number_format($remainSalary,2);
				$arrOther[2]["VALUE_TEXT_PROPS"] = ["color" => "red"];
				$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
				$arrCredit["LOANTYPE_DESC"] = $rowCredit["LOANTYPE_DESC"];
				$arrCredit["LOANTYPE_CODE"] = $rowCredit["LOANTYPE_CODE"];
				$arrCredit['LOAN_PERMIT_AMT'] = $loan_amt ?? 0;
				$arrCredit['MAXLOAN_AMT'] = $loan_amt ?? 0;
				$arrCredit['LOAN_RECEIVE_NET'] = $loan_amt - $interest ?? 0;
				$arrCredit["OLD_CONTRACT"] = [];
				$arrCredit["OTHER_INFO"] = $arrOther;
				$arrGroupCredit[] = $arrCredit;
			}
		}else{
			$fetchCredit = $conoracle->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt as MAXLOAN_AMT,LT.loantype_code as LOANTYPE_CODE,
												(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (NVL(mb.salary_amount,15000)*lc.multiple_salary ) AS CREDIT_AMT
												FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,
												shsharemaster sm LEFT JOIN mbmembmaster mb ON sm.member_no = mb.member_no,shsharetype sh
												WHERE mb.member_no = :member_no AND sm.SHAREMASTER_STATUS = '1' AND LT.LOANGROUP_CODE IN ( '01','02' )
												AND LT.LOANTYPE_CODE IN (".implode(",",$arrayLoantype).")
												AND TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time
												AND sm.sharestk_amt*sh.unitshare_value BETWEEN lc.startshare_amt AND lc.endshare_amt
												AND NVL(mb.salary_amount,15000) BETWEEN lc.startsalary_amt AND lc.endsalary_amt
												GROUP BY LT.loantype_code,lt.loantype_desc,lc.maxloan_amt,(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (NVL(mb.salary_amount,15000)*lc.multiple_salary)");
			$fetchCredit->execute([':member_no' => $member_no]);
			while($rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC)){
				$arrCredit = array();
				if($rowCredit["CREDIT_AMT"] > $rowCredit["MAXLOAN_AMT"]){
					$loan_amt = $rowCredit["MAXLOAN_AMT"];
				}else{
					$loan_amt = $rowCredit["CREDIT_AMT"];
				}
				if($loan_amt % 1000 > 0){
					$loan_amt = floor($loan_amt - ($loan_amt % 1000));
				}
				$getMaxPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM LNLOANTYPEPERIOD WHERE LOANTYPE_CODE = ? and money_from <= ? and money_to >= ?");
				$getMaxPeriod->execute([$rowCredit["LOANTYPE_CODE"],$loan_amt,$loan_amt]);
				$rowMaxPeriod = $getMaxPeriod->fetch(PDO::FETCH_ASSOC);
				$arrCredit["MAX_PERIOD"] = $rowMaxPeriod["MAX_PERIOD"];
				$getIntRate = $conoracle->prepare("SELECT LIR.INTEREST_RATE AS INTEREST_RATE
																	FROM LNLOANTYPE LP LEFT JOIN LNCFLOANINTRATEDET LIR
																	ON LP.INTTABRATE_CODE = LIR.LOANINTRATE_CODE WHERE SYSDATE
																	BETWEEN TO_CHAR(LIR.EFFECTIVE_DATE, 'YYYYMMDD') AND TO_CHAR(LIR.EXPIRE_DATE, 'YYYYMMDD') AND LP.LOANTYPE_CODE = :loantype_code");
				$getIntRate->execute([':loantype_code' => $rowCredit["LOANTYPE_CODE"]]);
				$rowIntRate = $getIntRate->fetch(PDO::FETCH_ASSOC);
				$int_rate = $rowIntRate["INTEREST_RATE"] / 100;
				$intCal = (($loan_amt * $int_rate) * 31) / 365;
				$pay_period = $loan_amt / $rowMaxPeriod["MAX_PERIOD"];
				$pay_period = floor($pay_period + (100 - ($pay_period % 100)));
				$interest = $lib->roundDecimal($intCal,1);
				$remainSalary = $pay_period;
				$arrOther = array();
				$arrOther[0]["LABEL"] = "ชำระต้นต่องวด";
				$arrOther[0]["VALUE"] = number_format($pay_period,2);
				$arrOther[1]["LABEL"] = "ประมาณการดอกเบี้ย";
				$arrOther[1]["VALUE"] = number_format($interest,2);
				$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
				$arrCredit["LOANTYPE_DESC"] = $rowCredit["LOANTYPE_DESC"];
				$arrCredit["LOANTYPE_CODE"] = $rowCredit["LOANTYPE_CODE"];
				$arrCredit['LOAN_PERMIT_AMT'] = $loan_amt ?? 0;
				$arrCredit['MAXLOAN_AMT'] = $loan_amt ?? 0;
				$arrCredit['LOAN_RECEIVE_NET'] = $loan_amt - $interest ?? 0;
				$arrCredit["OLD_CONTRACT"] = [];
				$arrCredit["OTHER_INFO"] = $arrOther;
				$arrGroupCredit[] = $arrCredit;

			}
		}
		$arrayResult["NOTE_INPUT_TITLE"] = "";
		$arrayResult["NOTE_INPUT"] = "*** ค่าใช้จ่ายในสลิปเงินเดือนทั้งหมด และค่าหุ้นสหกรณ์ ยกเว้นเงินต้นและดอกเบี้ย";
		$arrayResult["NOTE_INPUT_TEXT_COLOR"] = "red";
		$arrayResult["NOTE"] = "ผลการคำนวณข้างต้นเป็นเพียงการประมาณการเท่านั้น​ การพิจารณาอนุมัติสินเชื่อเป็นไปตามหลักเกณฑ์และเงื่อนไขที่สหกรณ์ฯ​ กำหนด";
		$arrayResult["NOTE_TEXT_COLOR"] = "red";
		$arrayResult["INPUT_SALARY"] = TRUE;
		$arrayResult["INPUT_OTHER_EXP"] = TRUE;
		$arrayResult["LOAN_CREDIT"] = $arrGroupCredit;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
