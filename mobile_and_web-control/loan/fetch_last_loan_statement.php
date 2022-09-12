<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrGroupAccount = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmloan');
		$arrayResult['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$fetchLastStmAcc = $conoracle->prepare("SELECT * FROM (SELECT lnm.LCONT_ID as loancontract_no,lnm.LCONT_AMOUNT_SAL as LOAN_BALANCE,
												lnm.LCONT_APPROVE_SAL as APPROVE_AMT,lnm.LCONT_DATE as startcont_date,lnm.LCONT_SAL as period_payment,
												lnm.LCONT_MAX_INSTALL as PERIOD,
												lnm.LCONT_MAX_INSTALL - lnm.LCONT_NUM_INST as LAST_PERIOD,lnm.LCONT_PAY_LAST_DATE as LAST_OPERATE_DATE
												from LOAN_M_CONTACT lnm
												WHERE  lnm.account_id = :member_no and lnm.LCONT_STATUS_CONT IN('H','A')
												ORDER BY lnm.LCONT_PAY_LAST_DATE DESC) WHERE rownum <= 1");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowLoanLastSTM = $fetchLastStmAcc->fetch(PDO::FETCH_ASSOC);
		$contract_no = preg_replace('/\//','',$rowLoanLastSTM["LOANCONTRACT_NO"]);
		$arrContract = array();
		$arrContract["CONTRACT_NO"] = $contract_no;
		$arrContract["LOAN_BALANCE"] = number_format($rowLoanLastSTM["LOAN_BALANCE"],2);
		$arrContract["APPROVE_AMT"] = number_format($rowLoanLastSTM["APPROVE_AMT"],2);
		$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowLoanLastSTM["LAST_OPERATE_DATE"],'y-n-d');
		$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowLoanLastSTM["LAST_OPERATE_DATE"],'D m Y');
		$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowLoanLastSTM["STARTCONT_DATE"],'D m Y');
		$arrContract["PERIOD_PAYMENT"] = number_format($rowLoanLastSTM["PERIOD_PAYMENT"],2);
		$arrContract["PERIOD"] = $rowLoanLastSTM["LAST_PERIOD"].' / '.$rowLoanLastSTM["PERIOD"];
		$arrContract["DATA_TIME"] = date('H:i');
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_loan');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.LPD_NUM_INST > ".$dataComing["old_seq_no"] : "and lsm.LPD_NUM_INST > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.LPD_NUM_INST < ".$dataComing["old_seq_no"] : "and lsm.LPD_NUM_INST < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.LPD_NUM_INST < ".$dataComing["old_seq_no"] : "and lsm.LPD_NUM_INST < 999999";
		}
		$getStatement = $conoracle->prepare("SELECT * FROM (SELECT lsm.REMARK AS TYPE_DESC,lsm.LPD_DATE as operate_date,
											lsm.LPD_SAL as PRN_PAYMENT,lsm.LPD_NUM_INST as SEQ_NO,lsm.LPD_NO as SLIP_NO,
											lsm.LPD_INTE as INT_PAYMENT,lsm.LCONT_BAL_AMOUNT as loan_balance
											FROM LOAN_M_PAYDEPT lsm 
											WHERE lsm.LCONT_ID = :contract_no and lsm.LPD_DATE
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
											ORDER BY lsm.LPD_DATE DESC,lsm.PAGE DESC,lsm.LINE DESC ) WHERE rownum <= ".$rownum." ");
		$getStatement->execute([
			':contract_no' => $contract_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
			$arrSTM = array();
			$arrSTM["TYPE_DESC"] = $rowStm["TYPE_DESC"];
			$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["PRN_PAYMENT"] = number_format($rowStm["PRN_PAYMENT"],2);
			$arrSTM["INT_PAYMENT"] = number_format($rowStm["INT_PAYMENT"],2);
			$arrSTM["SUM_PAYMENT"] = number_format($rowStm["INT_PAYMENT"] + $rowStm["PRN_PAYMENT"],2);
			$arrSTM["LOAN_BALANCE"] = number_format($rowStm["LOAN_BALANCE"],2);
			$arrayGroupSTM[] = $arrSTM;
		}
		$arrayResult["HEADER"] = $arrContract;
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["RESULT"] = TRUE;
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