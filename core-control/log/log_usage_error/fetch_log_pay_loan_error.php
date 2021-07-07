<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logrepayloanerror')){
		$arrayGroup = array();
		$fetchLogRepayLoan = $conoracle->prepare("SELECT
												pay.id_repayloan,
												pay.member_no,
												pay.transaction_date,
												pay.deptaccount_no,
												pay.amt_transfer,
												pay.penalty_amt,
												pay.status_flag,
												pay.destination,
												pay.response_code,
												pay.response_message,
												login.device_name,
												login.channel
											FROM
												logrepayloan pay
											LEFT JOIN gcuserlogin login ON
												pay.id_userlogin = login.id_userlogin
											WHERE pay.status_flag ='0'
											ORDER BY pay.transaction_date DESC");
												
		$fetchLogRepayLoan->execute();
		while($rowLogRepayLoan = $fetchLogRepayLoan->fetch(PDO::FETCH_ASSOC)){
			$arrLogRepayLoan["MEMBER_NO"] = $rowLogRepayLoan["MEMBER_NO"];
			$arrLogRepayLoan["CHANNEL"] = $rowLogRepayLoan["CHANNEL"];
			$arrLogRepayLoan["TRANSACTION_DATE"] =  $lib->convertdate($rowLogRepayLoan["TRANSACTION_DATE"],'d m Y',true); 
			$arrLogRepayLoan["DEVICE_NAME"] = stream_get_contents($rowLogRepayLoan["DEVICE_NAME"]);
			$arrLogRepayLoan["AMT_TRANSFER"] = $rowLogRepayLoan["AMT_TRANSFER"];
			$arrLogRepayLoan["PENALTY_AMT"] = $rowLogRepayLoan["PENALTY_AMT"];
			$arrLogRepayLoan["AMT_TRANSFER_FORMAT"] = number_format($rowLogRepayLoan["AMT_TRANSFER"],2);
			$arrLogRepayLoan["PENALTY_AMT_FORMAT"] = number_format($rowLogRepayLoan["PENALTY_AMT"],2);
			$arrLogRepayLoan["RESPONSE_CODE"] = $rowLogRepayLoan["RESPONSE_CODE"];
			$arrLogRepayLoan["RESPONSE_MESSAGE"] = stream_get_contents($rowLogRepayLoan["RESPONSE_MESSAGE"]);
			$arrLogRepayLoan["DEPTACCOUNT_NO"] = $rowLogRepayLoan["DEPTACCOUNT_NO"];
			$arrLogRepayLoan["DESTINATION"] = $rowLogRepayLoan["DESTINATION"];
			$arrLogRepayLoan["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowLogRepayLoan["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrLogRepayLoan["UNIQE_ID"] = $rowLogRepayLoan["UNIQUE_ID"];
			$arrLogRepayLoan["STATUS_FLAG"] = $rowLogRepayLoan["STATUS_FLAG"];
			$arrayGroup[] = $arrLogRepayLoan;
		}
		
		$arrayResult["LOG_PAY_LOAN_DATA"] = $arrayGroup;
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