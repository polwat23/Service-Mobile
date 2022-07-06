<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logtransfer',$conoracle)){
		$arrayGroup = array();
		$fetLogTranfer = $conoracle->prepare("SELECT 
													trans.ref_no,trans.member_no,
													trans.transaction_type_code,
													trans.from_account,
													trans.destination_type,
													trans.destination,
													trans.transfer_mode,
													trans.amount,
													trans.fee_amt,
													trans.penalty_amt,
													trans.amount_receive,
													trans.trans_flag,
													trans.operate_date,
													trans.result_transaction,
													trans.ref_no_1,trans.coop_slip_no,
													trans.ref_no_source,
													login.device_name,login.channel	
												FROM gctransaction trans
												LEFT JOIN gcuserlogin login
												ON login.id_userlogin = trans.id_userlogin
												WHERE trans.transfer_mode !='9'
												ORDER BY trans.operate_date DESC");
		$fetLogTranfer->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowLogTransfer = $fetLogTranfer->fetch(PDO::FETCH_ASSOC)){
			$arrLogTransfer = array();
			$arrLogTransfer["REF_NO"] = $rowLogTransfer["REF_NO"];
			$arrLogTransfer["MEMBER_NO"] = $rowLogTransfer["MEMBER_NO"];
			$arrLogTransfer["CHANNEL"] = $rowLogTransfer["CHANNEL"];
			$arrLogTransfer["DEVICE_NAME"] = $rowLogTransfer["DEVICE_NAME"];
			$arrLogTransfer["TRANSACTION_TYPE_CODE"] = $rowLogTransfer["TRANSACTION_TYPE_CODE"];
			$arrLogTransfer["FROM_ACCOUNT"] = $rowLogTransfer["FROM_ACCOUNT"];
			$arrLogTransfer["FROM_ACCOUNT_FORMAT"]= $lib->formataccount($rowLogTransfer["FROM_ACCOUNT"],$formatDept);
			$arrLogTransfer["DESTINATION_TYPE"] = $rowLogTransfer["DESTINATION_TYPE"];
			$arrLogTransfer["DESTINATION"] = $rowLogTransfer["DESTINATION"];
			$arrLogTransfer["DESTINATION_FORMAT"]= $lib->formataccount($rowLogTransfer["DESTINATION"],$formatDept);
			$arrLogTransfer["TRANSFER_MODE"] = $rowLogTransfer["TRANSFER_MODE"];
			$arrLogTransfer["AMOUNT"] = $rowLogTransfer["AMOUNT"];
			$arrLogTransfer["AMOUNT_FORMAT"] = number_format($rowLogTransfer["AMOUNT"],2);
			$arrLogTransfer["FEE_AMT"] = $rowLogTransfer["FEE_AMT"];
			$arrLogTransfer["FEE_AMT_FORMAT"] = number_format($rowLogTransfer["FEE_AMT"],2);
			$arrLogTransfer["PENALTY_AMT"] = $rowLogTransfer["PENALTY_AMT"];
			$arrLogTransfer["PENALTY_AMT_FORMAT"] = number_format( $rowLogTransfer["PENALTY_AMT"],2);
			$arrLogTransfer["AMOUNT_RECEIVE"] = $rowLogTransfer["AMOUNT_RECEIVE"];
			$arrLogTransfer["AMOUNT_RECEIVE_FORMAT"] = number_format($rowLogTransfer["AMOUNT_RECEIVE"],2);
			$arrLogTransfer["TRANS_FLAG"] = $rowLogTransfer["TRANS_FLAG"];
			$arrLogTransfer["RESULT_TRANSACTION"] = $rowLogTransfer["RESULT_TRANSACTION"];
			
			$arrLogTransfer["OPERATE_DATE"] =  $lib->convertdate($rowLogTransfer["OPERATE_DATE"],'d m Y',true); 
			
			$arrLogTransfer["REF_NO_1"] = $rowLogTransfer["REF_NO_1"];
			$arrLogTransfer["COOP_SLIP_NO"] = $rowLogTransfer["COOP_SLIP_NO"];
			$arrLogTransfer["REF_NO_SOURCE"] = $rowLogTransfer["REF_NO_SOURCE"];
		
			$arrayGroup[] = $arrLogTransfer;
		}
		$arrayResult["LOG_TRANSFER_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>