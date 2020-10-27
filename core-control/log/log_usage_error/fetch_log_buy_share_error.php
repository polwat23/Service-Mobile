<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logbuyshareerror')){
		$arrayGroup = array();
		$fetchLogShare = $conmysql->prepare("SELECT
														s.id_buyshare,
														s.member_no,
														s.transaction_date,
														s.deptaccount_no,
														s.amt_transfer,
														s.status_flag,
														s.destination,
														s.response_code,
														s.response_message,
														login.unique_id,
														login.device_name,
														login.channel
													FROM
														logbuyshare s
													LEFT JOIN gcuserlogin login ON
														login.id_userlogin = s.id_userlogin
													WHERE s.status_flag = '0'
													ORDER BY
														s.transaction_date
													DESC");
		$fetchLogShare->execute();
		while($rowLogShare = $fetchLogShare->fetch(PDO::FETCH_ASSOC)){
			$arrLogShare = array();
			$arrLogShare["ID_BUYSHARE"] = $rowLogShare["id_buyshare"];
			$arrLogShare["MEMBER_NO"] = $rowLogShare["member_no"];
			$arrLogShare["CHANNEL"] = $rowLogShare["channel"];
			$arrLogShare["TRANSACTION_DATE"] =  $lib->convertdate($rowLogShare["transaction_date"],'d m Y',true); 
			$arrLogShare["DEVICE_NAME"] = $rowLogShare["device_name"];
			$arrLogShare["AMT_TRANSFER"] = $rowLogShare["amt_transfer"];
			
			$arrLogShare["AMT_TRANSFER_FORMAT"] =number_format($rowLogShare["amt_transfer"],2);
			$arrLogShare["PENALTY_AMT_FORMAT"] =number_format($rowLogShare["penalty_amt"],2);
			$arrLogShare["RESPONSE_CODE"] = $rowLogShare["response_code"];
			$arrLogShare["DEPTACCOUNT_NO"] = $rowLogShare["deptaccount_no"];
			$arrLogShare["DESTINATION"] = $rowLogShare["destination"];
			$arrLogShare["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowLogShare["deptaccount_no"],$func->getConstant('dep_format'));
			$arrLogShare["DESTINATION_FORMAT"] = $lib->formataccount($rowLogShare["destination"],$func->getConstant('dep_format'));
			$arrLogShare["RESPONSE_MESSAGE"] = $rowLogShare["response_message"];
			$arrLogShare["UNIQE_ID"] = $rowLogShare["unique_id"];
			$arrLogShare["STATUS_FLAG"] = $rowLogShare["status_flag"];

			$arrayGroup[] = $arrLogShare;
		}
		$arrayResult["LOG_BUY_SHARE_DATA"] = $arrayGroup;
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