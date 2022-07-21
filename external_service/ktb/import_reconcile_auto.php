<?php
require_once('../autoloadExt.php');

if($lib->checkCompleteArgument(['list_reconcile'],$dataComing)){
	$arrImp = array();
	foreach($dataComing["list_reconcile"] as $list_reconcile){
		$insertImpReconcile = $conoracle->prepare("INSERT INTO reconcilewithdrawktb(processing_date, processing_time, transaction_type,credit_amount, fee_amount, net_amount, payer_account_no, payee_account_no, payee_account_bank_code, reference1, 
							reference2, effective_date, response_code, response_desc, transref_no, sys_ref_no, channel_id, filler) 
							VALUES('".$list_reconcile['PROCESSING_DATE']."','".$list_reconcile['PROCESSING_TIME']."','".$list_reconcile['TRANSACTION_TYPE']."','".$list_reconcile['CREDIT_AMOUNT']."'
							,'".$list_reconcile['FEE_AMOUNT']."','".$list_reconcile['NET_AMOUNT']."','".$list_reconcile['PAYER_ACCOUNT_NO']."','".$list_reconcile['PAYEE_ACCOUNT_NO']."'
							,'".$list_reconcile['PAYEE_ACCOUNT_BANK_CODE']."','".$list_reconcile['REFERENCE1']."','".$list_reconcile['REFERENCE2']."','".$list_reconcile['EFFECTIVE_DATE']."'
							,'".$list_reconcile['RESPONSE_CODE']."','".$list_reconcile['RESPONSE_DESC']."','".$list_reconcile['TRANSREF_NO']."','".$list_reconcile['SYS_REF_NO']."'
							,'".$list_reconcile['CHANNEL_ID']."','".$list_reconcile['FILLER']."')");
		if($insertImpReconcile->execute()){
			echo "insert complete";
		}else{
			echo "not insert";
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS9004";
	$arrayResult['RESPONSE_MESSAGE'] = "dataComing not complete";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
	
}
?>