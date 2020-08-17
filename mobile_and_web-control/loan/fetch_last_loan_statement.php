<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayResult = array();
		$arrGroupAccount = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmloan');
		$arrayResult['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$fetchLastStmAcc = $conoracle->prepare("SELECT * FROM (SELECT lnm.loancontract_no,lt.LOANTYPE_CODE,lt.LOANTYPE_DESC AS LOAN_TYPE,lnm.principal_balance as LOAN_BALANCE,
												lnm.loanapprove_amt as APPROVE_AMT,lnm.startcont_date,lnm.period_payment,lnm.period_payamt as PERIOD,
												LAST_PERIODPAY as LAST_PERIOD,lns.operate_date as LAST_OPERATE_DATE
												from lncontmaster lnm LEFT JOIN lncontstatement lns ON 
												lnm.loancontract_no = lns.loancontract_no LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE 
												WHERE lnm.member_no = :member_no and lnm.contract_status > 0
												and lns.entry_date IS NOT NULL ORDER BY lns.entry_date DESC) WHERE rownum <= 1");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowLoanLastSTM = $fetchLastStmAcc->fetch(PDO::FETCH_ASSOC);
		$contract_no = preg_replace('/\//','',$rowLoanLastSTM["LOANCONTRACT_NO"]);
		$arrContract = array();
		$getIntRate = $conoracle->prepare("SELECT * FROM(SELECT INTEREST_RATE from lnloantype ln LEFT JOIN lncfloanintratedet lit ON 
											ln.inttabrate_code = lit.loanintrate_code
											WHERE TRUNC(SYSDATE) > TRUNC(lit.EFFECTIVE_DATE) and ln.loantype_code = :loantype_code
											ORDER BY lit.EFFECTIVE_DATE DESC)
											WHERE rownum <= 1");
		$getIntRate->execute([':loantype_code' => $rowLoanLastSTM["LOANTYPE_CODE"]]);
		$rowIntRate = $getIntRate->fetch(PDO::FETCH_ASSOC);
		$arrContract["INT_RATE"] = ($rowIntRate["INTEREST_RATE"] * 100)."%";
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
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO > ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and lsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and lsm.SEQ_NO < 999999";
		}
		$i = 0;
		$getStatement = $conoracle->prepare("SELECT * FROM (SELECT lit.LOANITEMTYPE_CODE,lit.LOANITEMTYPE_DESC AS TYPE_DESC,lsm.operate_date,lsm.principal_payment as PRN_PAYMENT,lsm.SEQ_NO,lsm.REF_DOCNO,
											lsm.interest_payment as INT_PAYMENT,lsm.principal_balance as loan_balance,cmt.MONEYTYPE_DESC
											FROM lncontstatement lsm LEFT JOIN LNUCFLOANITEMTYPE lit
											ON lsm.LOANITEMTYPE_CODE = lit.LOANITEMTYPE_CODE
											LEFT JOIN cmucfmoneytype cmt ON lsm.MONEYTYPE_CODE = cmt.MONEYTYPE_CODE
											WHERE lsm.loancontract_no = :contract_no and lsm.operate_date
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
											ORDER BY lsm.SEQ_NO DESC) WHERE rownum <= ".$rownum." ");
		$getStatement->execute([
			':contract_no' => $contract_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
			$arrSTM = array();
			if($rowStm["LOANITEMTYPE_CODE"] == 'LPM'){
				if($i == 0){
					$getRecvPeriod = $conoracle->prepare("SELECT RECV_PERIOD,RECEIVE_AMT FROM kpmastreceive WHERE receipt_no = '".$rowStm["REF_DOCNO"]."'");
					$getRecvPeriod->execute();
					$rowRecvPeriod = $getRecvPeriod->fetch(PDO::FETCH_ASSOC);
					if(isset($rowRecvPeriod["RECV_PERIOD"]) && $rowRecvPeriod["RECV_PERIOD"] != ""){
						$arrSendKP = array();
						$arrSTM["IS_KEEPING"] = TRUE;
						$arrSTM["RECV_PERIOD"] = $rowRecvPeriod["RECV_PERIOD"];
						$arrSendKP["MONTH_RECEIVE"] = $lib->convertperiodkp(trim($rowRecvPeriod["RECV_PERIOD"]));
						$arrSendKP["SLIP_NO"] = $rowStm["REF_DOCNO"];
						$arrSendKP["RECEIVE_AMT"] = number_format($rowRecvPeriod["RECEIVE_AMT"],2);
						$arrSTM["SEND_KP"] = $arrSendKP;
					}
				}
				$i++;
			}
			$arrSTM["TYPE_DESC"] = $rowStm["TYPE_DESC"];
			$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["PAY_CHANNEL"] = $rowStm["MONEYTYPE_DESC"];
			$arrSTM["PRN_PAYMENT"] = number_format($rowStm["PRN_PAYMENT"],2);
			$arrSTM["INT_PAYMENT"] = number_format($rowStm["INT_PAYMENT"],2);
			$arrSTM["SUM_PAYMENT"] = number_format($rowStm["INT_PAYMENT"] + $rowStm["PRN_PAYMENT"],2);
			$arrSTM["LOAN_BALANCE"] = number_format($rowStm["LOAN_BALANCE"],2);
			$arrayGroupSTM[] = $arrSTM;
		}
		$arrayResult["HEADER"] = $arrContract;
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>