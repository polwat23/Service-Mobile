<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositonline',$conoracle)){
		$arrayGroup = array();
		$fetLogDepositOnline = $conoracle->prepare("SELECT 
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
												INNER JOIN gcuserlogin login
												ON login.id_userlogin = trans.id_userlogin
												WHERE trans.trans_flag = '1'
													  AND transfer_mode ='9'
												ORDER BY trans.operate_date DESC");
		$fetLogDepositOnline->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowLogDepositOnline = $fetLogDepositOnline->fetch(PDO::FETCH_ASSOC)){
			$arrLogDepositOnline = array();
			$arrLogDepositOnline["REF_NO"] = $rowLogDepositOnline["REF_NO"];
			$arrLogDepositOnline["MEMBER_NO"] = $rowLogDepositOnline["MEMBER_NO"];
			$arrLogDepositOnline["CHANNEL"] = $rowLogDepositOnline["CHANNEL"];
			$arrLogDepositOnline["DEVICE_NAME"] = $rowLogDepositOnline["DEVICE_NAME"];
			$arrLogDepositOnline["TRANSACTION_TYPE_CODE"] = $rowLogDepositOnline["TRANSACTION_TYPE_CODE"];
			$arrLogDepositOnline["FROM_ACCOUNT"] = $rowLogDepositOnline["FROM_ACCOUNT"];
			$arrLogDepositOnline["FROM_ACCOUNT_FORMAT"]= $lib->formataccount($rowLogDepositOnline["FROM_ACCOUNT"],$formatDept);
			$arrLogDepositOnline["DESTINATION_TYPE"] = $rowLogDepositOnline["DESTINATION_TYPE"];
			$arrLogDepositOnline["DESTINATION"] = $rowLogDepositOnline["DESTINATION"];
			$arrLogDepositOnline["DESTINATION_FORMAT"]= $lib->formataccount($rowLogDepositOnline["DESTINATION"],$formatDept);
			$arrLogDepositOnline["TRANSFER_MODE"] = $rowLogDepositOnline["TRANSFER_MODE"];
			$arrLogDepositOnline["AMOUNT"] = $rowLogDepositOnline["AMOUNT"];
			$arrLogDepositOnline["AMOUNT_FORMAT"] = number_format($rowLogDepositOnline["AMOUNT"],2);
			$arrLogDepositOnline["FEE_AMT"] = $rowLogDepositOnline["FEE_AMT"];
			$arrLogDepositOnline["FEE_AMT_FORMAT"] = number_format($rowLogDepositOnline["FEE_AMT"],2);
			$arrLogDepositOnline["PENALTY_AMT"] = $rowLogDepositOnline["PENALTY_AMT"];
			$arrLogDepositOnline["PENALTY_AMT_FORMAT"] = number_format( $rowLogDepositOnline["PENALTY_AMT"],2);
			$arrLogDepositOnline["AMOUNT_RECEIVE"] = $rowLogDepositOnline["AMOUNT_RECEIVE"];
			$arrLogDepositOnline["AMOUNT_RECEIVE_FORMAT"] = number_format($rowLogDepositOnline["AMOUNT_RECEIVE"],2);
			$arrLogDepositOnline["TRANS_FLAG"] = $rowLogDepositOnline["TRANS_FLAG"];
			$arrLogDepositOnline["RESULT_TRANSACTION"] = $rowLogDepositOnline["RESULT_TRANSACTION"];
			
			$arrLogDepositOnline["OPERATE_DATE"] =  $lib->convertdate($rowLogDepositOnline["OPERATE_DATE"],'d m Y',true); 
			
			$arrLogDepositOnline["REF_NO_1"] = $rowLogDepositOnline["REF_NO_1"];
			$arrLogDepositOnline["COOP_SLIP_NO"] = $rowLogDepositOnline["COOP_SLIP_NO"];
			$arrLogDepositOnline["REF_NO_SOURCE"] = $rowLogDepositOnline["REF_NO_SOURCE"];
		
			$arrayGroup[] = $arrLogDepositOnline;
		}
		$arrayResult["LOG_DEPOSIT_DATA"] = $arrayGroup;
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