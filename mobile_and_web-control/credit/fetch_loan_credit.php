<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupCredit = array();
		$oldBal = 0;
		
		$getShareBF = $conmssql->prepare("SELECT (SHARESTK_AMT * 10) AS SHARESTK_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT FROM shsharemaster WHERE member_no = :member_no");
		$getShareBF->execute([':member_no' => $member_no]);
		$rowShareBF = $getShareBF->fetch(PDO::FETCH_ASSOC);
		$getFundColl = $conmssql->prepare("SELECT SUM(EST_PRICE) as FUND_AMT FROM LNCOLLMASTER WHERE COLLMAST_TYPE = '05' AND MEMBER_NO = :member_no");
		$getFundColl->execute([':member_no' => $member_no]);
		$rowFund = $getFundColl->fetch(PDO::FETCH_ASSOC);
		$loan_amt = $rowShareBF["SHARESTK_AMT"] + $rowFund["FUND_AMT"];
		
		
		//ดึงข้อมูลสัญญาเดิม
		$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE
											FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
											WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
		$getOldContract->execute([
			':member_no' => $member_no
		]);

		while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
			$oldBal += $rowOldContract["PRINCIPAL_BALANCE"];
		}
		$estimate_amt = ($loan_amt ?? 0) - $oldBal;
		$arrCredit = array();
		$arrCredit["LOANTYPE_DESC"] = "ประมาณการสิทธิ์กู้";
		$arrCredit["LOANTYPE_CODE"] = null;
		if($estimate_amt > 0){
			$arrCredit['ESTIMATE_AMT'] = $estimate_amt;
		}else{
			$arrCredit['ESTIMATE_AMT'] = 0;
		}
		$arrCredit['ESTIMATE_REMARK'] = "ทุนเรือนหุ้น + กองทุน ".number_format($loan_amt,2)." | หนี้คงเหลือ : ".number_format($oldBal,2);
		$arrCredit["OLD_CONTRACT"] = [];
		$arrGroupCredit[] = $arrCredit;
			
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