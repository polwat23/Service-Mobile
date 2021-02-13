<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','reconcile_data','operate_date'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconcile')){
		$arrayGroup = array();
		$arrayCheckReconcile = array();
		$fetchTrans = $conmysql->prepare("SELECT ref_no,from_account,destination,amount,fee_amt,penalty_amt,amount_receive,operate_date,member_no
											FROM gctransaction 
											WHERE date(operate_date) = :operate_date AND result_transaction = '1' AND trans_flag = '-1' AND transfer_mode = '9'");
		$fetchTrans->execute([
			':operate_date' => $dataComing['operate_date']
		]);
		$arrayGroup["COOP_RECONCILE"] = array();
		$arrayGroup["BANK_RECONCILE"] = array();
		while($rowTrans = $fetchTrans->fetch()){
			$arrTrans = array();
			$arrTrans["REF_NO"] = $rowTrans["ref_no"];
			$arrTrans["FROM_ACCOUNT"] = $rowTrans["from_account"];
			$arrTrans["DESTINATION"] = $lib->formataccount($rowTrans["destination"],$func->getConstant('dep_format'));
			$arrTrans["AMOUNT"] = number_format($rowTrans["amount"],2);
			$arrTrans["FEE_AMT"] = number_format($rowTrans["fee_amt"],2);
			$arrTrans["PENALTY_AMT"] = number_format($rowTrans["penalty_amt"],2);
			$arrTrans["AMOUNT_RECEIVE"] = number_format($rowTrans["amount_receive"],2);
			$arrTrans["OPERATE_DATE"] = $lib->convertdate($rowTrans["operate_date"],'d m Y',true);
			$arrTrans["MEMBER_NO"] = $rowTrans["member_no"];
			$arrTrans["NET_AMOUNT"] = number_format($rowTrans["amount"]+$rowTrans["fee_amt"],2);
			$arrayGroup["COOP_RECONCILE"][] = $arrTrans;
		}
		
		$arrayResult['BANK_RECONCILE'] = $dataComing["reconcile_data"];
		$arrayResult['COOP_RECONCILE'] = $arrayGroup["COOP_RECONCILE"];
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