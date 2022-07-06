<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logwithdrawerror',$conoracle)){
		$arrayGroup = array();
		$fetchLogWithdrawError = $conoracle->prepare("SELECT wd.id_withdrawtransbankerr,
														wd.member_no,
														wd.transaction_date,
														wd.amt_transfer,
														wd.penalty_amt,
														wd.penalty_amt,
														wd.fee_amt,
														wd.fee_amt,
														wd.deptaccount_no,
														wd.response_code,
														wd.response_message,
														login.channel,
														login.device_name
													FROM logwithdrawtransbankerror wd
													INNER JOIN gcuserlogin login
													ON login.id_userlogin = wd.id_userlogin
													ORDER BY 	wd.transaction_date DESC ");
		$fetchLogWithdrawError->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowLogWithdrawError = $fetchLogWithdrawError->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogWithdrawError = array();
			$arrGroupLogWithdrawError["ID_WITHDRAW_TRAN_BANK_ERROR"] = $rowLogWithdrawError["ID_WITHDRAWTRANSBANKERR"];
			$arrGroupLogWithdrawError["MEMBER_NO"] = $rowLogWithdrawError["MEMBER_NO"];
			$arrGroupLogWithdrawError["CHANNEL"] = $rowLogWithdrawError["CHANNEL"];
			$arrGroupLogWithdrawError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogWithdrawError["TRANSACTION_DATE"],'d m Y',true); 
			$arrGroupLogWithdrawError["DEVICE_NAME"] = $rowLogWithdrawError["DEVICE_NAME"];
			$arrGroupLogWithdrawError["AMT_TRANSFER"] = $rowLogWithdrawError["AMT_TRANSFER"];
			$arrGroupLogWithdrawError["AMT_TRANSFER_FORMAT"] = number_format($rowLogWithdrawError["AMT_TRANSFER"],2);
			$arrGroupLogWithdrawError["PENALTY_AMT"] = $rowLogWithdrawError["PENALTY_AMT"];
			$arrGroupLogWithdrawError["PENALTY_AMT_FORMAT"] =number_format( $rowLogWithdrawError["PENALTY_AMT"],2);
			$arrGroupLogWithdrawError["FEE_AMT"] = $rowLogWithdrawError["FEE_AMT"];
			$arrGroupLogWithdrawError["FEE_AMT_FORMAT"] =  number_format($rowLogWithdrawError["FEE_AMT"],2);
			$arrGroupLogWithdrawError["DEPTACCOUNT_NO"] = $rowLogWithdrawError["DEPTACCOUNT_NO"];
			$arrGroupLogWithdrawError["DEPTACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogWithdrawError["DEPTACCOUNT_NO"],$formatDept);
			$arrGroupLogWithdrawError["RESPONSE_CODE"] = $rowLogWithdrawError["RESPONSE_CODE"];
			$arrGroupLogWithdrawError["RESPONSE_MESSAGE"] = $rowLogWithdrawError["RESPONSE_MESSAGE"];
			
	
			
			$arrayGroup[] = $arrGroupLogWithdrawError;
		}
		$arrayResult["LOG_WITHDRAW_ERROR_DATA"] = $arrayGroup;
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