<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','depttransaction')){
		$arrayExecute = array();
		$arrayGrpAll = array();
		
		if(isset($dataComing["trrans_type"]) && $dataComing["trrans_type"] != ""){
			if($dataComing["trrans_type"] == "payloan"){
				$arrayExecute["trrans_type"] = "2";
			}else if($dataComing["trrans_type"] == "buyshare"){
				$arrayExecute["trrans_type"] = "3";
			}else if($dataComing["trrans_type"] == "transfer"){
				$arrayExecute["trrans_type"] = "1";
			}else {
				$arrayExecute["trrans_type"] = "9";
			}
		}else{
			$arrayExecute["trrans_type"] = "9";
		}
		
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
				$arrayExecute["member_no"] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
			}
			if($dataComing["date_type"] == 'year'){
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){ 
				$fetchReconcile = $conoracle->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){ 
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM-DD') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM-DD') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}
		}else{
			if(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != ""){
				$arrayExecute["ref_no"] = $dataComing["ref_no"];
			}
			if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ""){
				$arrayExecute["member_no"] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
			}
			if($dataComing["date_type"] == 'year'){
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}
		}
		$fetchReconcile->execute($arrayExecute);

		$summary = 0;
		$formatDept = $func->getConstant('dep_format');
		while($rowRecon = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
			$arrayRecon = array();
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["TRANSACTION_TYPE_CODE"];
			$arrayRecon["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowRecon["FROM_ACCOUNT"],$formatDept);
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["FROM_ACCOUNT"];
			if($rowRecon["transfer_mode"] == '1'){
				$arrayRecon["DESTINATION_FORMAT"] = $lib->formataccount($rowRecon["DESTINATION"],$formatDept);
			}else{
				$arrayRecon["DESTINATION_FORMAT"] = $rowRecon["DESTINATION"];
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
			$arrayRecon["RECEIVE_AMT"] = number_format($rowRecon["AMOUNT_RECEIVE"],2);
			
			if( $rowRecon["RESULT_TRANSACTION"]=='1'){
				$summary += $rowRecon["AMOUNT_RECEIVE"];
			}else{
				$summary += 0;
			}
			$arrayGrpAll[] = $arrayRecon;
		}
		$arrayResult['SUMMARY'] = $summary;
		$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
		$arrayResult['DEPT_TRANSACTION'] = $arrayGrpAll;
		$arrayResult['arrayExecute'] = $arrayExecute;
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
