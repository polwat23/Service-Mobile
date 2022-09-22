<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanStatement')){
		
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmloan');
		$arrayResult['LIMIT_DURATION'] = $limit;
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$contract_no = $dataComing["contract_no"];
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_loan');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO > ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO < 999999";
		}
		$getAccount = $conmssql->prepare("SELECT principal_balance as LOAN_BALANCE FROM lncontmaster
											WHERE contract_status = 1 and loancontract_no = :contract_no");
		$getAccount->execute([
			':contract_no' => $contract_no
		]);
		$rowContract = $getAccount->fetch(PDO::FETCH_ASSOC);
		$arrayHeaderAcc["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
		$arrayHeaderAcc["DATA_TIME"] = date('H:i');
		$getStatement = $conmssql->prepare("SELECT TOP ".$rownum." lit.LOANITEMTYPE_DESC AS TYPE_DESC,lsm.OPERATE_DATE,lsm.principal_payment as PRN_PAYMENT,lsm.SEQ_NO,
											lsm.interest_payment as INT_PAYMENT,lsm.principal_balance as LOAN_BALANCE , lsm.PERIOD , lsm.LOANITEMTYPE_CODE
											FROM lncontstatement lsm LEFT JOIN LNUCFLOANITEMTYPE lit
											ON lsm.LOANITEMTYPE_CODE = lit.LOANITEMTYPE_CODE
											WHERE RTRIM(lsm.loancontract_no) = :contract_no and lsm.LOANITEMTYPE_CODE <> 'AVG' and lsm.OPERATE_DATE
											BETWEEN CONVERT(varchar, :datebefore, 23) and CONVERT(varchar, :datenow, 23) ".$old_seq_no." 
											ORDER BY lsm.SEQ_NO DESC");
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
			if($rowStm["LOANITEMTYPE_CODE"] != "LRC"){
				$arrSTM["PERIOD"] = $rowStm["PERIOD"];
			}
			$arrayGroupSTM[] = $arrSTM;
		}
		$arrayResult["HEADER"] = $arrayHeaderAcc;
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["LIMIT_FETCH_STM_LOAN"] = $rownum;
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