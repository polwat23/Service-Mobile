<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','depttransaction')){
		$arrayExecute = array();
		$arrayGrpAll = array();
		
		if(isset($dataComing["trrans_type"]) && $dataComing["trrans_type"] != ""){
			if($dataComing["trrans_type"] == "payloan"){
				$arrayExecute["trrans_type"] = "4";
			}else if($dataComing["trrans_type"] == "buyshare"){
				$arrayExecute["trrans_type"] = "3";
			}else if($dataComing["trrans_type"] == "transfer"){
				$arrayExecute["trrans_type"] = "1";
			}else if($dataComing["trrans_type"] == "receiveloan"){
				$arrayExecute["trrans_type"] = "2";
			}else if($dataComing["trrans_type"] == "exttransfer"){
				$arrayExecute["trrans_type"] = "9";
			}else {
				$arrayExecute["trrans_type"] = "-1";
			}
		}else{
			$arrayExecute["trrans_type"] = "-1";
		}
		
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
			
		$fetchReconcile = $conmssql->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,transfer_mode,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no
														FROM gctransaction
														WHERE (transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and operate_date >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and operate_date <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
														
		$fetchReconcile->execute($arrayExecute);

		$summary = 0;
		$formatDept = $func->getConstant('dep_format');
		while($rowRecon = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
			$arrayRecon = array();
			$arrayRecon["TRANSFER_MODE"] = $rowRecon["transfer_mode"];
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["transaction_type_code"];
			$arrayRecon["FROM_ACCOUNT_FORMAT"] = $rowRecon["from_account"];
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["from_account"];
			$arrayRecon["DESTINATION_FORMAT"] = (isset($dataComing["destination"]) && $dataComing["destination"] != "") ?
											$rowRecon["destination"] : null;
			$arrayRecon["REF_NO"] = $rowRecon["ref_no"];
			$arrayRecon["DESTINATION"] = $rowRecon["destination"];
			$arrayRecon["TRANS_FLAG"] = $rowRecon["trans_flag"];
			$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["operate_date"],'d m Y',true);
			$arrayRecon["AMOUNT"] = number_format($rowRecon["amount"],2);
			$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["penalty_amt"],2);
			$arrayRecon["FEE_AMT"] = number_format($rowRecon["fee_amt"],2);
			$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["result_transaction"];
			$arrayRecon["MEMBER_NO"] = $rowRecon["member_no"];
			$arrayRecon["RECEIVE_AMT"] = number_format($rowRecon["amount_receive"],2);
			if($rowRecon["transfer_mode"] == '1'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายใน';
			} else if($rowRecon["transfer_mode"] == '4' || ($rowRecon["transfer_mode"] == '9' && $rowRecon["transaction_type_code"] == 'WFS')){
				$arrayRecon["TRANSFER_TYPE"] = 'ชำระหนี้';
			} else if($rowRecon["transfer_mode"] == '3'){
				$arrayRecon["TRANSFER_TYPE"] = 'ซื้อหุ้น';
			} else if($rowRecon["transfer_mode"] == '2' || ($rowRecon["transfer_mode"] == '9' && $rowRecon["transaction_type_code"] == 'DAP')){
				$arrayRecon["TRANSFER_TYPE"] = 'รับเงินกู้';
			} else if($rowRecon["transfer_mode"] == '9'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายนอก';
			}
			
			if( $rowRecon["result_transaction"]=='1'){
				$summary += $rowRecon["amount_receive"];
			}else{
				$summary += 0;
			}
			$arrayGrpAll[] = $arrayRecon;
		}
		$arrayResult['SUMMARY'] = $summary;
		$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
		$arrayResult['DEPT_TRANSACTION'] = $arrayGrpAll;
		$arrayResult['arrayExecute'] = $arrayExecute;
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