<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','reconcile_data','operate_date'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconcile')){
		$arrayGroup = array();
		$arrayCheckReconcile = array();
		$fetchTrans = $conoracle->prepare("SELECT ref_no,from_account,destination,amount,fee_amt,penalty_amt,amount_receive,operate_date,member_no
											FROM gctransaction 
											WHERE operate_date = TO_DATE(:operate_date,'yyyy/mm/dd') AND result_transaction = '1' AND trans_flag = '-1' AND transfer_mode = '9'");
		$fetchTrans->execute([
			':operate_date' => $dataComing['operate_date']
		]);
					$arrTrans = array();
			$arrTrans["REF_NO"] = $rowTrans["REF_NO"];
			$arrTrans["FROM_ACCOUNT"] = $rowTrans["FROM_ACCOUNT"];
			$arrTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$func->getConstant('dep_format'));
			$arrTrans["AMOUNT"] = number_format($rowTrans["AMOUNT"],2);
			$arrTrans["FEE_AMT"] = number_format($rowTrans["FEE_AMT"],2);
			$arrTrans["PENALTY_AMT"] = number_format($rowTrans["PENALTY_AMT"],2);
			$arrTrans["AMOUNT_RECEIVE"] = number_format($rowTrans["AMOUNT_RECEIVE"],2);
			$arrTrans["OPERATE_DATE"] = $lib->convertdate($rowTrans["OPERATE_DATE"],'d m Y',true);
			$arrTrans["MEMBER_NO"] = $rowTrans["MEMBER_NO"];
			$arrTrans["NET_AMOUNT"] = number_format($rowTrans["AMOUNT"]+$rowTrans["FEE_AMT"],2);
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