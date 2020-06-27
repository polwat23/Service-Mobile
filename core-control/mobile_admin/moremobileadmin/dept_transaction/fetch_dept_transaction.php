<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','depttransaction')){
		$arrayExecute = array();
		$arrayGrpAll = array();
	
		if($dataComing["trrans_type"]=='transaction'){
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
				if(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != ""){
				$arrayExecute["trans_flag"] = $dataComing["trans_flag"];
			}
			if($dataComing["date_type"] == 'year'){
				$fetchReconcile = $conmysql->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode != '9' and result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(operate_date,'%Y') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag = :trans_flag " : null)
														." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode != '9' and result_transaction = '1'
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m') >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(operate_date,'%Y-%m') <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag = :trans_flag " : null)
														." ORDER BY operate_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReconcile = $conmysql->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode != '9' and result_transaction = '1'
												".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no = :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)."
														".(isset($dataComing["trans_flag"]) && $dataComing["trans_flag"] != "" ? 
														"and trans_flag =  :trans_flag " : null)."".
														"ORDER BY operate_date DESC");
			}
				
		}else if($dataComing["trrans_type"]=='payloan'){
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
						$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode = '2' and result_transaction = '1'
																".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and date_format(operate_date,'%Y') >= :start_date" : null)."
																".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and date_format(operate_date,'%Y') <= :end_date" : null)." 
																".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
																"and ref_no <= :ref_no" : null)." 
																".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
																"and member_no = :member_no" : null)
																." ORDER BY operate_date DESC");
					}else if($dataComing["date_type"] == 'month'){
						$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode = '2' and result_transaction = '1'
																".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and date_format(operate_date,'%Y-%m') >= :start_date" : null)."
																".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and date_format(operate_date,'%Y-%m') <= :end_date" : null)." 
																".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
																"and ref_no <= :ref_no" : null)." 
																".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
																"and member_no = :member_no" : null)
																." ORDER BY operate_date DESC");
					}else if($dataComing["date_type"] == 'day'){
						$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode = '2' and result_transaction = '1'
																".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and date_format(operate_date,'%Y-%m-%d') >= :start_date" : null)."
																".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and date_format(operate_date,'%Y-%m-%d') <= :end_date" : null)." 
																".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
																"and ref_no <= :ref_no" : null)." 
																".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
																"and member_no = :member_no" : null)
																." ORDER BY operate_date DESC");
																
					}
					
				}else if($dataComing["trrans_type"]=='buyshare'){
					
					
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
						$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode = '3' and result_transaction = '1'
																".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and date_format(operate_date,'%Y') >= :start_date" : null)."
																".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and date_format(operate_date,'%Y') <= :end_date" : null)." 
																".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
																"and ref_no <= :ref_no" : null)." 
																".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
																"and member_no = :member_no" : null)
																." ORDER BY operate_date DESC");
					}else if($dataComing["date_type"] == 'month'){
						$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode = '3' and result_transaction = '1'
																".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and date_format(operate_date,'%Y-%m') >= :start_date" : null)."
																".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and date_format(operate_date,'%Y-%m') <= :end_date" : null)." 
																".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
																"and ref_no <= :ref_no" : null)." 
																".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
																"and member_no = :member_no" : null)
																." ORDER BY operate_date DESC");
						}else if($dataComing["date_type"] == 'day'){
							$fetchReconcile = $conmysql->prepare("SELECT ref_no, trans_flag,transaction_type_code,from_account,destination,operate_date,amount,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no,destination_type
														FROM gctransaction
														WHERE transfer_mode = '3' and result_transaction = '1'
																	".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																	"and date_format(operate_date,'%Y-%m-%d') >= :start_date" : null)."
																	".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																	"and date_format(operate_date,'%Y-%m-%d') <= :end_date" : null)." 
																	".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
																	"and ref_no <= :ref_no" : null)." 
																	".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
																	"and member_no = :member_no" : null)
																	." ORDER BY operate_date DESC");
						}
				}else{
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
		$fetchReconcile->execute($arrayExecute);
			$summary = 0;
					while($rowRecon = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
						$arrayRecon = array();
						$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["transaction_type_code"];
						$arrayRecon["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowRecon["from_account"],$func->getConstant('dep_format'));
						$arrayRecon["FROM_ACCOUNT"] = $rowRecon["from_account"];
						$arrayRecon["DESTINATION"] = $rowRecon["destination"];
						$arrayRecon["REF_NO"] = $rowRecon["ref_no"];
						$arrayRecon["DESTINATION_FORMAT"] = $rowRecon["destination_type"]=='1'?$lib->formataccount($rowRecon["destination"],$func->getConstant('dep_format')):$rowRecon["destination"];
						$arrayRecon["TRANS_FLAG"] = $rowRecon["trans_flag"];
						$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["operate_date"],'d m Y',true);
						$arrayRecon["AMOUNT"] = number_format($rowRecon["amount"],2);
						$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["penalty_amt"],2);
						$arrayRecon["FEE_AMT"] = number_format($rowRecon["fee_amt"],2);
						$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["result_transaction"];
						$arrayRecon["MEMBER_NO"] = $rowRecon["member_no"];
						$arrayRecon["RECEIVE_AMT"] = number_format($rowRecon["amount_receive"],2);
						$summary=$summary += $rowRecon["amount_receive"];
					
						$arrayGrpAll[] = $arrayRecon;
					}
					$arrayResult['SUMMARY'] = $summary;
					$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
					$arrayResult['DEPT_TRANSACTION'] = $arrayGrpAll;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
