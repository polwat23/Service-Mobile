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
				$fetchReconcile = $conoracle->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  and result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY')) <= :end_date" : null)." 
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
				$fetchReconcile = $conoracle->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  and  result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM') <= :end_date" : null)." 
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
				$fetchReconcile = $conoracle->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
														gt.penalty_amt,gt.fee_amt,gt.amount_receive,gt.result_transaction,gt.member_no,coop_slip_no,gt.bank_code,csb.bank_short_ename
														FROM gctransaction gt
														LEFT JOIN csbankdisplay csb ON csb.bank_code = gt.bank_code
														WHERE transfer_mode = '9'  and  result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM-DD') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM-DD') <= :end_date" : null)." 
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
				$fetchReconcile = $conoracle->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
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
				$fetchReconcile = $conoracle->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
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
				$fetchReconcile = $conoracle->prepare("SELECT gt.ref_no,gt.trans_flag,gt.transaction_type_code,gt.from_account,gt.destination,gt.operate_date,gt.amount,
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
		$fetchFormatAccBank = $conoracle->prepare("SELECT bank_format_account FROM csbankdisplay WHERE bank_code = :bank");
		$fetchFormatAccBank->execute([':bank' => isset($dataComing["bank"]) && $dataComing["bank"] != "" ? $dataComing["bank"] : '006']);
		$rowFormatAcc = $fetchFormatAccBank->fetch(PDO::FETCH_ASSOC);
		$summary = 0;
		$formatDept = $func->getConstant('dep_format');
		while($rowRecon = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
			$arrayRecon = array();
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["TRANSACTION_TYPE_CODE"];
			if($rowRecon["TRANS_FLAG"] == '1'){
				$arrayRecon["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowRecon["FROM_ACCOUNT"],$rowFormatAcc["BANK_FORMAT_ACCOUNT"]);
			}else{
				$arrayRecon["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowRecon["FROM_ACCOUNT"],$formatDept);
			}
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["FROM_ACCOUNT"];
			if($rowRecon["TRANS_FLAG"] == '1'){
				$arrayRecon["DESTINATION_FORMAT"] = $lib->formataccount($rowRecon["DESTINATION"],$formatDept);
			}else{
				$arrayRecon["DESTINATION_FORMAT"] = $lib->formataccount($rowRecon["DESTINATION"],$rowFormatAcc["BANK_FORMAT_ACCOUNT"]);
			}
			$arrayRecon["REF_NO"] = $rowRecon["REF_NO"];
			$arrayRecon["DESTINATION"] = $rowRecon["DESTINATION"];
			$arrayRecon["TRANS_FLAG"] = $rowRecon["TRANS_FLAG"];
			$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["OPERATE_DATE"],'d m Y',true);
			$arrayRecon["AMOUNT"] = number_format($rowRecon["AMOUNT"],2);
			$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["PENALTY_AMT"],2);
			$arrayRecon["FEE_AMT"] = number_format($rowRecon["FEE_AMT"],2);
			$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["RESULT_TRANSACTION"];
			$arrayRecon["MEMBER_NO"] = $rowRecon["MEMBER_NO"];
			$arrayRecon["COOP_SLIP_NO"] = $rowRecon["COOP_SLIP_NO"];
			$arrayRecon["BANK_CODE"] = $rowRecon["BANK_CODE"];
			$arrayRecon["BANK_NAME"] = $rowRecon["BANK_SHORT_ENAME"];
			$arrayRecon["RECEIVE_AMT"] = number_format($rowRecon["AMOUNT_RECEIVE"],2);
			
			$summary += $rowRecon["AMOUNT_RECEIVE"];
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