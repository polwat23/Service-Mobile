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
		$fetchCredit = $conmssql->prepare("SELECT lt.loantype_desc AS LOANTYPE_DESC,lc.maxloan_amt as MAXLOAN_AMT,LT.loantype_code as LOANTYPE_CODE,
											(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (ISNULL(mb.salary_amount,15000)*lc.multiple_salary ) AS CREDIT_AMT
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,
											shsharemaster sm LEFT JOIN mbmembmaster mb ON sm.member_no = mb.member_no,shsharetype sh
											WHERE mb.member_no = :member_no AND sm.SHAREMASTER_STATUS = '1' AND LT.LOANGROUP_CODE IN ( '01','02' )
											AND LT.LOANTYPE_CODE IN (".implode(",",$arrayLoantype).")
											AND (CASE WHEN DATEDIFF(dd, EOMONTH(mb.member_date), EOMONTH(getdate())) = 0 THEN 0
											ELSE DATEDIFF(mm, mb.member_date, getdate()) - 1 END) BETWEEN lc.startmember_time AND lc.endmember_time
											AND sm.sharestk_amt*sh.unitshare_value BETWEEN lc.startshare_amt AND lc.endshare_amt
											AND ISNULL(mb.salary_amount,15000) BETWEEN lc.startsalary_amt AND lc.endsalary_amt
											GROUP BY LT.loantype_code,lt.loantype_desc,lc.maxloan_amt,(sm.sharestk_amt*sh.unitshare_value*lc.multiple_share ) + (ISNULL(mb.salary_amount,15000)*lc.multiple_salary)");
		$fetchCredit->execute([':member_no' => $member_no]);
		while($rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC)){
			$arrCredit = array();
			if($rowCredit["CREDIT_AMT"] > $rowCredit["MAXLOAN_AMT"]){
				$loan_amt = $rowCredit["MAXLOAN_AMT"];
			}else{
				$loan_amt = $rowCredit["CREDIT_AMT"];
			}
			$arrCredit["LOANTYPE_DESC"] = $rowCredit["LOANTYPE_DESC"];
			$arrCredit["LOANTYPE_CODE"] = $rowCredit["LOANTYPE_CODE"];
			$arrCredit['LOAN_PERMIT_AMT'] = $loan_amt ?? 0;
			$arrCredit['MAXLOAN_AMT'] = $loan_amt ?? 0;
			$arrCredit["OLD_CONTRACT"] = [];
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