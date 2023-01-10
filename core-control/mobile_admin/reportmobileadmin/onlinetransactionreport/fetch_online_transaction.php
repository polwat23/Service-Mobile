<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','onlinetransactionreport')){
		$arrayExecute = array();
		$arrayGrpAll = array();
		$deptItemTypeData = array();
		$getDeptItemType = $conoracle->prepare("SELECT DEPTITEMTYPE_CODE,DEPTITEMTYPE_DESC,SIGN_FLAG FROM DPUCFDEPTITEMTYPE");
		$getDeptItemType->execute();
		while($rowDeptItemType = $getDeptItemType->fetch(PDO::FETCH_ASSOC)){
			$arrDeptItemType = array();
			$arrDeptItemType["DEPTITEMTYPE_CODE"] = $rowDeptItemType["DEPTITEMTYPE_CODE"];
			$arrDeptItemType["DEPTITEMTYPE_DESC"] = $rowDeptItemType["DEPTITEMTYPE_DESC"];
			$arrDeptItemType["SIGN_FLAG"] = $rowDeptItemType["SIGN_FLAG"];
			$deptItemTypeData[] = $arrDeptItemType;
		}
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
														"and date_format(operate_date,'%Y') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
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
														"and date_format(operate_date,'%Y-%m') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m') <= :end_date" : null)." 
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
														"and date_format(operate_date,'%Y-%m-%d') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m-%d') <= :end_date" : null)." 
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
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
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
				':member_no' => $rowRecon["member_no"]
			]);
			$rowName = $getMemName->fetch(PDO::FETCH_ASSOC);
			$arrayRecon["FULL_NAME"] = $rowName["MEMB_NAME"] .' '. $rowName["MEMB_SURNAME"];
			
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["transaction_type_code"];
			$arrayRecon["FROM_ACCOUNT_FORMAT"] = $rowRecon["transfer_mode"] != "2" ? $rowRecon["from_account"] : $rowRecon["from_account"];
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["from_account"];
			$arrayRecon["DESTINATION_FORMAT"] = $rowRecon["destination"];
			$arrayRecon["REF_NO"] = $rowRecon["ref_no"];
			$arrayRecon["DESTINATION"] = $rowRecon["destination"];
			$arrayRecon["TRANS_FLAG"] = $rowRecon["trans_flag"];
			$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["operate_date"],'d m Y',true);
			$arrayRecon["AMOUNT"] = number_format($rowRecon["amount"],2);
			$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["penalty_amt"],2);
			$arrayRecon["FEE_AMT"] = number_format($rowRecon["fee_amt"],2);
			$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["result_transaction"];
			$arrayRecon["MEMBER_NO"] = $rowRecon["member_no"];
			$arrayRecon["RECEIVE_AMT"] = number_format(($rowRecon["amount_receive"]+$rowRecon["fee_amt"]),2);
			if($rowRecon["transfer_mode"] == '1'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายใน';
			} else if($rowRecon["transfer_mode"] == '2' || ($rowRecon["transfer_mode"] == '9' && $rowRecon["transaction_type_code"] == 'WFS')){
				$arrayRecon["TRANSFER_TYPE"] = 'ชำระหนี้';
			} else if($rowRecon["transfer_mode"] == '3'){
				$arrayRecon["TRANSFER_TYPE"] = 'ซื้อหุ้น';
			} else if($rowRecon["transfer_mode"] == '4' || ($rowRecon["transfer_mode"] == '9' && $rowRecon["transaction_type_code"] == 'DAP')){
				$arrayRecon["TRANSFER_TYPE"] = 'จ่ายเงินกู้';
			} else if($rowRecon["transfer_mode"] == '9'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายนอก';
			} else if($rowRecon["transfer_mode"] == '9' && ($rowRecon["trans_flag"] == '-1' && $rowRecon["transaction_type_code"] == 'WIM')){
				$arrayRecon["TRANSFER_TYPE"] = 'ถอน';
			} else if($rowRecon["transfer_mode"] == '5' && ($rowRecon["trans_flag"] == '-1' && $rowRecon["transaction_type_code"] == 'DTX')){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมผ่าน QR';
			}else if($rowRecon["transfer_mode"] == '9' && ($rowRecon["trans_flag"] == '1' && $rowRecon["transaction_type_code"] == 'DIM')){
				$arrayRecon["TRANSFER_TYPE"] = 'ฝาก';
			}
			
			if( $rowRecon["result_transaction"]=='1'){
				$summary += ($rowRecon["amount_receive"]+$rowRecon["fee_amt"]);
			}else{
				$summary += 0;
			}
			$arrayGrpAll[] = $arrayRecon;
		}
		$arrayResult['SUMMARY'] = $summary;
		$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
		$arrayResult['DEPT_TRANSACTION'] = $arrayGrpAll;
		$arrayResult['fetchReconcile'] = $fetchReconcile;
		$arrayResult['DEPT_ITEM_TYPE_DATA'] = $deptItemTypeData;
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
