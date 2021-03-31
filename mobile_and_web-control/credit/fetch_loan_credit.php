<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupCredit = array();
		$arrCanCal = array();
		$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_creditloan = '1'");
		$fetchLoanCanCal->execute();
		while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
			$arrCanCal[] = $rowCanCal["loantype_code"];
		}
		$fetchCredit = $conmysqlcoop->prepare("SELECT lt.loantype_code as LOANTYPE_CODE,lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt,lc.percentshare,lc.percentsalary,mb.salary_amount,(sh.sharestk_amt*10) as SHARE_AMT
													FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb 
													LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
													WHERE mb.member_no = :member_no and 
													LT.LOANTYPE_CODE IN(".implode(',',$arrCanCal).")
													and DATE(TIMESTAMPDIFF(MONTH, now(),mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
		$fetchCredit->execute([':member_no' => $member_no]);
		while($rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC)){
			$arrCredit = array();
			$maxloan_amt = 0;
			$salaryPercent = $rowCredit["SALARY_AMOUNT"] * $rowCredit["PERCENTSALARY"];
			$sharePercent = $rowCredit["SHARE_AMT"] * $rowCredit["PERCENTSHARE"];
			if($salaryPercent > $sharePercent){
				$maxloan_amt = $salaryPercent;
			}else{
				$maxloan_amt = $sharePercent;
			}
			if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
				$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
			}
			$arrCredit["LOANTYPE_CODE"] = $rowCredit["LOANTYPE_CODE"];
			$arrCredit["LOANTYPE_DESC"] = $rowCredit["LOANTYPE_DESC"];
			$arrCredit["LOAN_PERMIT_AMT"] = $maxloan_amt;
			$arrGroupCredit[] = $arrCredit;
		}
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