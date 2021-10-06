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
		$fetchLastStmAcc = $conmssqlcoop->prepare("SELECT  TOP 1 dt.deposit_type as DEPTTYPE_CODE,dt.description as DEPTTYPE_DESC,dm.deposit_id as DEPTACCOUNT_NO,
														dm.description as DEPTACCOUNT_NAME,dpt.balance as BALANCE,
														(SELECT max(transaction_date) FROM codeposit_transaction WHERE deposit_id = dm.deposit_id) as LAST_OPERATE_DATE
														FROM  codeposit_master dm  LEFT JOIN codeposit_type dt ON dm.deposit_type = dt.deposit_type
														LEFT JOIN codeposit_transaction dpt ON dm.lastseq = dpt.transaction_seq  and dm.deposit_id = dpt.deposit_id  and dpt.transaction_subseq = 0
														WHERE dm.member_id = :member_no and dm.status = 'A' order by dm.deposit_id ASC");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowAccountLastSTM = $fetchLastStmAcc->fetch(PDO::FETCH_ASSOC);
		$account_no = $rowAccountLastSTM["DEPTACCOUNT_NO"];
		$arrAccount = array();
		$account_no_format = isset($account_no) && $account_no != "" ? $account_no : null;
		$arrAccount["DEPTACCOUNT_NO"] = $account_no_format;
		$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = isset($account_no_format) ? $account_no_format : null;
		$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccountLastSTM["DEPTACCOUNT_NAME"]));
		$arrAccount["BALANCE"] = number_format($rowAccountLastSTM["BALANCE"],2);
		$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccountLastSTM["LAST_OPERATE_DATE"],'y-n-d');
		$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccountLastSTM["LAST_OPERATE_DATE"],'D m Y');
		$arrAccount["DATA_TIME"] = date('H:i');
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_dept');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and stm.TRANSACTION_SEQ > ".$dataComing["old_seq_no"] : "and stm.TRANSACTION_SEQ > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and stm.TRANSACTION_SEQ < ".$dataComing["old_seq_no"] : "and stm.TRANSACTION_SEQ < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and stm.TRANSACTION_SEQ < ".$dataComing["old_seq_no"] : "and stm.TRANSACTION_SEQ < 999999";
		}
		
		$getStatement = $conmssqlcoop->prepare("SELECT TOP ".$rownum." dt.transaction_description as TYPE_TRAN,dt.transaction_action as SIGN_FLAG ,stm.WITHDRAWAL , stm.DEPOSIT , stm.balance as PRNCBAL,
											stm.transaction_date as OPERATE_DATE ,stm.transaction_seq as SEQ_NO
											FROM codeposit_transaction stm LEFT JOIN  codeposit_transactiontype dt  ON stm.transaction_type = dt.transaction_type AND   stm.transaction_subseq = 0 
											where  stm.deposit_id = ? and  stm.transaction_subseq = '0'
											and stm.transaction_date BETWEEN CONVERT(varchar, ? , 23) and CONVERT(varchar, ? , 23) ".$old_seq_no." 
											ORDER BY stm.transaction_seq DESC");
		
		$getStatement->execute([$account_no,$date_before,$date_now]);
		$getMemoDP = $conmssql->prepare("SELECT memo_text,memo_icon_path,seq_no FROM gcmemodept 
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
			$arrSTM["SIGN_FLAG"] = $rowStm["SIGN_FLAG"];
			$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["TRAN_AMOUNT"] =  number_format($rowStm["WITHDRAWAL"] + $rowStm["DEPOSIT"],2);
			$arrSTM["PRIN_BAL"] = number_format($rowStm["PRNCBAL"],2);
			if(array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no')) === False){
				$arrSTM["MEMO_TEXT"] = null;
				$arrSTM["MEMO_ICON_PATH"] = null;
			}else{
				$arrSTM["MEMO_TEXT"] = $arrMemo[array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no'))]["memo_text"] ?? null;
				$arrSTM["MEMO_ICON_PATH"] = $arrMemo[array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no'))]["memo_icon_path"] ?? null;
			}
			$arrayGroupSTM[] = $arrSTM;
		}
		if($dataComing["fetch_type"] != 'more'){
			$arrayResult["HEADER"] = $arrAccount;
		}
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["DATE"]  = $account_no.$date_before.$date_now.'  '.$old_seq_no ;
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