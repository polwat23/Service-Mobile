<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logwithdrawonline',$conoracle)){
		$arrayGroup = array();
		$fetLogTransection = $conoracle->prepare("SELECT
													trans.ref_no,
													trans.member_no,
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
													trans.ref_no_1,
													trans.coop_slip_no,
													trans.ref_no_source,
													login.device_name,
													login.channel
												FROM
													gctransaction trans
												LEFT JOIN gcuserlogin login ON
													login.id_userlogin = trans.id_userlogin
												WHERE
													trans.trans_flag = '-1'
													AND transfer_mode ='9'
												ORDER BY trans.operate_date DESC");
												
		$fetLogTransection->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowLogTransection = $fetLogTransection->fetch(PDO::FETCH_ASSOC)){
			$arrLogTransection = array();
			$arrLogTransection["REF_NO"] = $rowLogTransection["REF_NO"];
			$arrLogTransection["MEMBER_NO"] = $rowLogTransection["MEMBER_NO"];
			$arrLogTransection["CHANNEL"] = $rowLogTransection["CHANNEL"];
			$arrLogTransection["DEVICE_NAME"] = $rowLogTransection["DEVICE_NAME"];
			$arrLogTransection["TRANSACTION_TYPE_CODE"] = $rowLogTransection["TRANSACTION_TYPE_CODE"];
			$arrLogTransection["FROM_ACCOUNT"] = $rowLogTransection["FROM_ACCOUNT"];
			$arrLogTransection["FROM_ACCOUNT_FORMAT"]= $lib->formataccount($rowLogTransection["FROM_ACCOUNT"],$formatDept);
			$arrLogTransection["DESTINATION_TYPE"] = $rowLogTransection["DESTINATION_TYPE"];
			$arrLogTransection["DESTINATION"] = $rowLogTransection["DESTINATION"];
			$arrLogTransection["DESTINATION_FORMAT"]= $lib->formataccount($rowLogTransection["DESTINATION"],$formatDept);
			$arrLogTransection["TRANSFER_MODE"] = $rowLogTransection["TRANSFER_MODE"];
			$arrLogTransection["AMOUNT"] = $rowLogTransection["AMOUNT"];
			$arrLogTransection["AMOUNT_FORMAT"] = number_format($rowLogTransection["AMOUNT"],2);
			$arrLogTransection["FEE_AMT"] = $rowLogTransection["FEE_AMT"];
			$arrLogTransection["FEE_AMT_FORMAT"] = number_format($rowLogTransection["FEE_AMT"],2);
			$arrLogTransection["PENALTY_AMT"] = $rowLogTransection["PENALTY_AMT"];
			$arrLogTransection["PENALTY_AMT_FORMAT"] = number_format( $rowLogTransection["PENALTY_AMT"],2);
			$arrLogTransection["AMOUNT_RECEIVE"] = $rowLogTransection["AMOUNT_RECEIVE"];
			$arrLogTransection["AMOUNT_RECEIVE_FORMAT"] = number_format($rowLogTransection["AMOUNT_RECEIVE"],2);
			$arrLogTransection["TRANS_FLAG"] = $rowLogTransection["TRANS_FLAG"];
			$arrLogTransection["RESULT_TRANSACTION"] = $rowLogTransection["RESULT_TRANSACTION"];
			
			$arrLogTransection["OPERATE_DATE"] =  $lib->convertdate($rowLogTransection["OPERATE_DATE"],'d m Y',true); 
			
			$arrLogTransection["REF_NO_1"] = $rowLogTransection["REF_NO_1"];
			$arrLogTransection["COOP_SLIP_NO"] = $rowLogTransection["COOP_SLIP_NO"];
			$arrLogTransection["REF_NO_SOURCE"] = $rowLogTransection["REF_NO_SOURCE"];

			$arrayGroup[] = $arrLogTransection;
		}
		$arrayResult["LOG_TRANSECTION_DATA"] = $arrayGroup;
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