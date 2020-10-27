<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logrepayloanerror')){
		$arrayGroup = array();
		$fetchLogRepayLoan = $conmysql->prepare("SELECT
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
											ORDER BY pay.transaction_date");
												
		$fetchLogRepayLoan->execute();
		while($rowLogRepayLoan = $fetchLogRepayLoan->fetch(PDO::FETCH_ASSOC)){
			$arrLogRepayLoan["MEMBER_NO"] = $rowLogRepayLoan["member_no"];
			$arrLogRepayLoan["MEMBER_NO"] = $rowLogRepayLoan["member_no"];
			$arrLogRepayLoan["CHANNEL"] = $rowLogRepayLoan["channel"];
			$arrLogRepayLoan["TRANSACTION_DATE"] =  $lib->convertdate($rowLogRepayLoan["transaction_date"],'d m Y',true); 
			$arrLogRepayLoan["DEVICE_NAME"] = $rowLogRepayLoan["device_name"];
			$arrLogRepayLoan["AMT_TRANSFER"] = $rowLogRepayLoan["amt_transfer"];
			$arrLogRepayLoan["PENALTY_AMT"] = $rowLogRepayLoan["penalty_amt"];
			$arrLogRepayLoan["AMT_TRANSFER_FORMAT"] =number_format($rowLogRepayLoan["amt_transfer"],2);
			$arrLogRepayLoan["PENALTY_AMT_FORMAT"] =number_format($rowLogRepayLoan["penalty_amt"],2);
			$arrLogRepayLoan["RESPONSE_CODE"] = $rowLogRepayLoan["response_code"];
			$arrLogRepayLoan["RESPONSE_MESSAGE"] = $rowLogRepayLoan["response_message"];
			$arrLogRepayLoan["DEPTACCOUNT_NO"] = $rowLogRepayLoan["deptaccount_no"];
			$arrLogRepayLoan["DESTINATION"] = $rowLogRepayLoan["destination"];
			$arrLogRepayLoan["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowLogRepayLoan["deptaccount_no"],$func->getConstant('dep_format'));
			$arrLogRepayLoan["UNIQE_ID"] = $rowLogRepayLoan["unique_id"];
			$arrLogRepayLoan["STATUS_FLAG"] = $rowLogRepayLoan["status_flag"];
			$arrayGroup[] = $arrLogRepayLoan;
		}
		
		$arrayResult["LOG_PAY_LOAN_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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