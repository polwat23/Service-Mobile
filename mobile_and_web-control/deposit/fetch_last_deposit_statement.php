<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrGroupAccount = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmdeposit');
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
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$fetchLastStmAcc = $conoracle->prepare("SELECT * from (SELECT dpm.account_no as deptaccount_no,dpm.account_name as deptaccount_name,dpm.BALANCE as BALANCE,
											(SELECT max(LAST_DATE) FROM BK_H_SAVINGACCOUNT WHERE account_id = dpm.account_id) as LAST_OPERATE_DATE
											FROM BK_H_SAVINGACCOUNT dpm
												WHERE dpm.account_id = :member_no and dpm.ACC_STATUS = 'O'
												ORDER BY dpm.LAST_DATE DESC) where rownum <= 1");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowAccountLastSTM = $fetchLastStmAcc->fetch(PDO::FETCH_ASSOC);
		$arrAccount = array();
		$account_no = $lib->formataccount($rowAccountLastSTM["DEPTACCOUNT_NO"],$formatDept);
		$arrAccount["DEPTACCOUNT_NO"] = $account_no;
		$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($account_no,$formatDeptHidden);
		$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccountLastSTM["DEPTACCOUNT_NAME"]));
		$arrAccount["BALANCE"] = number_format($rowAccountLastSTM["BALANCE"],2);
		$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccountLastSTM["LAST_OPERATE_DATE"],'y-n-d');
		$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccountLastSTM["LAST_OPERATE_DATE"],'D m Y');
		$arrAccount["DATA_TIME"] = date('H:i');
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_dept');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.BOOK_ID > ".$dataComing["old_seq_no"] : "and dsm.BOOK_ID > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.BOOK_ID < ".$dataComing["old_seq_no"] : "and dsm.BOOK_ID > 0";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.BOOK_ID < ".$dataComing["old_seq_no"] : "and dsm.BOOK_ID > 0";
		}
		$getAccount = $conoracle->prepare("SELECT BALANCE as BALANCE FROM BK_H_SAVINGACCOUNT
											WHERE account_no = :account_no and ACC_STATUS = 'O'");
		$getAccount->execute([
			':account_no' => $account_no
		]);
		$rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC);
		$arrayHeaderAcc["BALANCE"] = number_format($rowAccount["BALANCE"],2);
		$arrayHeaderAcc["SEQUEST_AMOUNT"] = number_format(0,2);
		$arrayHeaderAcc["DATA_TIME"] = date('H:i');
		$getStatement = $conoracle->prepare("SELECT * FROM (SELECT dit.TRANS_DESC AS TYPE_TRAN,dsm.PAGE_NO || dsm.LINE_NO as seq_no,
											dsm.CURR_DATE as operate_date,(dsm.DEP_CASH + dsm.WDL_CASH) as TRAN_AMOUNT,
											dsm.N_BALANCE as PRNCBAL,dsm.T_TRNS_TYPE as SIGN_FLAG,dsm.BOOK_ID,dsm.TRANS_CODE
											FROM BK_T_NOBOOK dsm LEFT JOIN BK_M_TRANSCODE dit
											ON dsm.TRANS_CODE = dit.TRANS_CODE 
											WHERE dsm.account_no = :account_no and TRUNC(dsm.CURR_DATE) 
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
											ORDER BY dsm.BOOK_ID DESC) WHERE rownum <= ".$rownum." ");
		$getStatement->execute([
			':account_no' => $rowAccountLastSTM["DEPTACCOUNT_NO"],
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		$getMemoDP = $conmysql->prepare("SELECT memo_text,memo_icon_path,seq_no FROM gcmemodept 
											WHERE deptaccount_no = :account_no");
		$getMemoDP->execute([
			':account_no' => $account_no
		]);
		$arrMemo = array();
		while($rowMemo = $getMemoDP->fetch(PDO::FETCH_ASSOC)){
			$arrMemo[] = $rowMemo;
		}
		while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
			$arrSTM = array();
			$arrSTM["TYPE_TRAN"] = $rowStm["TYPE_TRAN"];
			if(substr($rowStm["TRANS_CODE"],0,1) == 'D' || substr($rowStm["TRANS_CODE"],0,1) == 'O' || substr($rowStm["TRANS_CODE"],0,1) == 'T' || substr($rowStm["TRANS_CODE"],0,1) == 'P' || substr($rowStm["TRANS_CODE"],0,1) == 'B' || substr($rowStm["TRANS_CODE"],0,1) == 'E'){
				$arrSTM["SIGN_FLAG"] = '1';
			}else{
				$arrSTM["SIGN_FLAG"] = '-1';
			}
			$arrSTM["SEQ_NO"] = $rowStm["BOOK_ID"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["TRAN_AMOUNT"] = number_format($rowStm["TRAN_AMOUNT"],2);
			$arrSTM["PRIN_BAL"] = number_format($rowStm["PRNCBAL"],2);
			if(array_search($rowStm["BOOK_ID"],array_column($arrMemo,'seq_no')) === False){
				$arrSTM["MEMO_TEXT"] = null;
				$arrSTM["MEMO_ICON_PATH"] = null;
			}else{
				$arrSTM["MEMO_TEXT"] = $arrMemo[array_search($rowStm["BOOK_ID"],array_column($arrMemo,'seq_no'))]["memo_text"] ?? null;
				$arrSTM["MEMO_ICON_PATH"] = $arrMemo[array_search($rowStm["BOOK_ID"],array_column($arrMemo,'seq_no'))]["memo_icon_path"] ?? null;
			}
			$arrayGroupSTM[] = $arrSTM;
		}
		if($dataComing["fetch_type"] != 'more'){
			$arrayResult["HEADER"] = $arrAccount;
		}
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["REQUEST_STATEMENT"] = TRUE;
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