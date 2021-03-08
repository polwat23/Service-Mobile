<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconcile')){
		$arrayExecute = array();
		$arrayGrpAll = array();
		
		if(isset($dataComing["date_type"]) && $dataComing["date_type"] != ""){
			if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
				$arrayExecute["start_date"] = $dataComing["start_date"];
			}
			if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
				$arrayExecute["end_date"] = $dataComing["end_date"];
			}
			if(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != ""){
				$arrayExecute["ref_no"] = $dataComing["ref_no"];
			}
			if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ""){
				$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
			}
			if(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != ""){
				$arrayExecute["trans_flag"] = $dataComing["trans_flag"];
			}
			if(isset($dataComing["bank"]) && $dataComing["bank"] != ""){
				$arrayExecute["bank"] = $dataComing["bank"];
			}
		
			if($dataComing["date_type"] == 'year'){
				$fetchReconcile = $conmysql->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  and result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(operate_date,'%Y') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag = :trans_flag " : null).""."
														".(isset($dataComing["bank"]) && $dataComing["bank"] != "" ? 
														"and bank_code = :bank " : null)."".
														"ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReconcile = $conmysql->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  and  result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag = :trans_flag " : null).""."
														".(isset($dataComing["bank"]) && $dataComing["bank"] != "" ? 
														"and bank_code = :bank " : null)."".
														" ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReconcile = $conmysql->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  and  result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m-%d') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m-%d') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag = :trans_flag " : null).""."
														".(isset($dataComing["bank"]) && $dataComing["bank"] != "" ? 
														"and bank_code = :bank " : null)."".
														"ORDER BY operate_date DESC");
			}
		}else{
			if(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != ""){
				$arrayExecute["ref_no"] = $dataComing["ref_no"];
			}
			if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ""){
				$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
			}
			if(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != ""){
				$arrayExecute["trans_flag"] = $dataComing["trans_flag"];
			}
			if(isset($dataComing["bank"]) && $dataComing["bank"] != ""){
				$arrayExecute["bank"] = $dataComing["bank"];
			}

			if($dataComing["date_type"] == 'year'){
				$fetchReconcile = $conmysql->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9' and  result_transaction = '1'
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag = :trans_flag " : null).""."
														".(isset($dataComing["bank"]) && $dataComing["bank"] != "" ? 
														"and bank_code = :bank " : null)."".
														"ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReconcile = $conmysql->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9' and  result_transaction = '1'
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." 
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag =  :trans_flag " : null).""." 
														".(isset($dataComing["bank"]) && $dataComing["bank"] != "" ? 
														"and bank_code =  :bank " : null)."".
														"ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReconcile = $conmysql->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  result_transaction = '1'
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag =  :trans_flag " : null).""."
														".(isset($dataComing["bank"]) && $dataComing["bank"] != "" ? 
														"and bank_code =  :bank " : null)."".
														"ORDER BY operate_date DESC");
			}
		}
		$fetchReconcile->execute($arrayExecute);
		$fetchFormatAccBank = $conmysql->prepare("SELECT bank_format_account FROM csbankdisplay WHERE bank_code = :bank");
		$fetchFormatAccBank->execute([':bank' => isset($dataComing["bank"]) && $dataComing["bank"] != "" ? $dataComing["bank"] : '006']);
		$rowFormatAcc = $fetchFormatAccBank->fetch(PDO::FETCH_ASSOC);
		$summary = 0;
		$formatDept = $func->getConstant('dep_format');
		while($rowRecon = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
			$arrayRecon = array();
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["transaction_type_code"];
			if($rowRecon["trans_flag"] == '1'){
				$arrayRecon["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowRecon["from_account"],$rowFormatAcc["bank_format_account"]);
			}else{
				$arrayRecon["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowRecon["from_account"],$formatDept);
			}
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["from_account"];
			if($rowRecon["trans_flag"] == '1'){
				$arrayRecon["DESTINATION_FORMAT"] = $lib->formataccount($rowRecon["destination"],$formatDept);
			}else{
				$arrayRecon["DESTINATION_FORMAT"] = $lib->formataccount($rowRecon["destination"],$rowFormatAcc["bank_format_account"]);
			}
			$arrayRecon["REF_NO"] = $rowRecon["ref_no"];
			$arrayRecon["DESTINATION"] = $rowRecon["destination"];
			$arrayRecon["TRANS_FLAG"] = $rowRecon["trans_flag"];
			$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["operate_date"],'d m Y',true);
			$arrayRecon["AMOUNT"] = number_format($rowRecon["amount"],2);
			$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["penalty_amt"],2);
			$arrayRecon["FEE_AMT"] = number_format($rowRecon["fee_amt"],2);
			$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["result_transaction"];
			$arrayRecon["MEMBER_NO"] = $rowRecon["member_no"];
			$arrayRecon["COOP_SLIP_NO"] = $rowRecon["coop_slip_no"];
			$arrayRecon["BANK_CODE"] = $rowRecon["bank_code"];
			$arrayRecon["BANK_NAME"] = $rowRecon["bank_short_ename"];
			$arrayRecon["RECEIVE_AMT"] = number_format($rowRecon["amount_receive"],2);
			
			$summary += $rowRecon["amount_receive"];
			$arrayGrpAll[] = $arrayRecon;
		}
		
		$arrayResult['SUMMARY'] = $summary;
		$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
		$arrayResult['RECONCILE'] = $arrayGrpAll;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>