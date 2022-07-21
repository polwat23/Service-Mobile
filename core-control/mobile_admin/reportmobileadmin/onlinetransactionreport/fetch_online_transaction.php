<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','onlinetransactionreport',$conoracle)){
		$arrayExecute = array();
		$arrayGrpAll = array();
		
		if(isset($dataComing["trrans_type"]) && $dataComing["trrans_type"] != ""){
			if($dataComing["trrans_type"] == "payloan"){
				$arrayExecute["trrans_type"] = "2";
			}else if($dataComing["trrans_type"] == "buyshare"){
				$arrayExecute["trrans_type"] = "3";
			}else if($dataComing["trrans_type"] == "transfer"){
				$arrayExecute["trrans_type"] = "1";
			}else if($dataComing["trrans_type"] == "receiveloan"){
				$arrayExecute["trrans_type"] = "4";
			}else if($dataComing["trrans_type"] == "exttransfer"){
				$arrayExecute["trrans_type"] = "9";
			}else if($dataComing["trrans_type"] == "withdraw"){
				$arrayExecute["trrans_type"] = "9";
			}else if($dataComing["trrans_type"] == "deposit"){
				$arrayExecute["trrans_type"] = "9";
			}else if($dataComing["trrans_type"] == "internal_tran"){
				$arrayExecute["trrans_type"] != "9";
			}else {
				$arrayExecute["trrans_type"] = "-1";
			}
		}else{
			$arrayExecute["trrans_type"] = "-1";
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
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
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
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(operate_date,'YYYY-MM') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReconcile = $conmysql->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
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
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReconcile = $conoracle->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,transfer_mode
														FROM gctransaction
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
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

			$getMemName = $conoracle->prepare("select memb_name ,memb_surname  from mbmembmaster where member_no = :member_no");
			$getMemName->execute([
				':member_no' => $rowRecon["MEMBER_NO"]
			]);
			$rowName = $getMemName->fetch(PDO::FETCH_ASSOC);
			$arrayRecon["FULL_NAME"] = $rowName["MEMB_NAME"] .' '. $rowName["MEMB_SURNAME"];
			
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["TRANSACTION_TYPE_CODE"];
			$arrayRecon["FROM_ACCOUNT_FORMAT"] = $rowRecon["TRANSFER_MODE"] != "2" ? $rowRecon["FROM_ACCOUNT"] : $rowRecon["FROM_ACCOUNT"];
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["FROM_ACCOUNT"];
			$arrayRecon["DESTINATION_FORMAT"] = $rowRecon["DESTINATION"];
			$arrayRecon["REF_NO"] = $rowRecon["REF_NO"];
			$arrayRecon["DESTINATION"] = $rowRecon["DESTINATION"];
			$arrayRecon["TRANS_FLAG"] = $rowRecon["TRANS_FLAG"];
			$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["OPERATE_DATE"],'d m Y',true);
			$arrayRecon["AMOUNT"] = number_format($rowRecon["AMOUNT"],2);
			$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["PENALTY_AMT"],2);
			$arrayRecon["FEE_AMT"] = number_format($rowRecon["FEE_AMT"],2);
			$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["RESULT_TRANSACTION"];
			$arrayRecon["MEMBER_NO"] = $rowRecon["MEMBER_NO"];
			$arrayRecon["RECEIVE_AMT"] = number_format(($rowRecon["AMOUNT_RECEIVE"]+$rowRecon["FEE_AMT"]),2);
			if($rowRecon["TRANSFER_MODE"] == '1'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายใน';
			} else if($rowRecon["TRANSFER_MODE"] == '2' || ($rowRecon["TRANSFER_MODE"] == '9' && $rowRecon["TRANSACTION_TYPE_CODE"] == 'WFS')){
				$arrayRecon["TRANSFER_TYPE"] = 'ชำระหนี้';
			} else if($rowRecon["TRANSFER_MODE"] == '3'){
				$arrayRecon["TRANSFER_TYPE"] = 'ซื้อหุ้น';
			} else if($rowRecon["TRANSFER_MODE"] == '4' || ($rowRecon["TRANSFER_MODE"] == '9' && $rowRecon["TRANSACTION_TYPE_CODE"] == 'DAP')){
				$arrayRecon["TRANSFER_TYPE"] = 'จ่ายเงินกู้';
			} else if($rowRecon["TRANSFER_MODE"] == '9'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายนอก';
			} else if($rowRecon["TRANSFER_MODE"] == '9' && ($rowRecon["TRANS_FLAG"] == '-1' && $rowRecon["TRANSACTION_TYPE_CODE"] == 'WIM')){
				$arrayRecon["TRANSFER_TYPE"] = 'ถอน';
			} else if($rowRecon["TRANSFER_MODE"] == '9' && ($rowRecon["TRANS_FLAG"] == '1' && $rowRecon["TRANSACTION_TYPE_CODE"] == 'DIM')){
				$arrayRecon["TRANSFER_TYPE"] = 'ฝาก';
			}
			
			if( $rowRecon["RESULT_TRANSACTION"]=='1'){
				$summary += ($rowRecon["AMOUNT_RECEIVE"]+$rowRecon["FEE_AMT"]);
			}else{
				$summary += 0;
			}
			$arrayGrpAll[] = $arrayRecon;
		}
		$arrayResult['SUMMARY'] = $summary;
		$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
		$arrayResult['DEPT_TRANSACTION'] = $arrayGrpAll;
		$arrayResult['fetchReconcile'] = $fetchReconcile;
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
