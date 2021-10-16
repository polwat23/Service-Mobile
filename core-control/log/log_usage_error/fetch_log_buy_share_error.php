<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logbuyshareerror',$conoracle)){
		$arrayGroup = array();
		$fetchLogShare = $conoracle->prepare("SELECT
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
			$arrLogShare["ID_BUYSHARE"] = $rowLogShare["ID_BUYSHARE"];
			$arrLogShare["MEMBER_NO"] = $rowLogShare["MEMBER_NO"];
			$arrLogShare["CHANNEL"] = $rowLogShare["CHANNEL"];
			$arrLogShare["TRANSACTION_DATE"] =  $lib->convertdate($rowLogShare["TRANSACTION_DATE"],'d m Y',true); 
			$arrLogShare["DEVICE_NAME"] = $rowLogShare["DEVICE_NAME"];
			$arrLogShare["AMT_TRANSFER"] = $rowLogShare["AMT_TRANSFER"];
			
			$arrLogShare["AMT_TRANSFER_FORMAT"] =number_format($rowLogShare["AMT_TRANSFER"],2);
			$arrLogShare["PENALTY_AMT_FORMAT"] =number_format($rowLogShare["PENALTY_AMT"],2);
			$arrLogShare["RESPONSE_CODE"] = $rowLogShare["RESPONSE_CODE"];
			$arrLogShare["DEPTACCOUNT_NO"] = $rowLogShare["DEPTACCOUNT_NO"];
			$arrLogShare["DESTINATION"] = $rowLogShare["DESTINATION"];
			$arrLogShare["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowLogShare["DEPTACCOUNT_NO"],$func->getConstant('dep_format',$conoracle));
			$arrLogShare["DESTINATION_FORMAT"] = $lib->formataccount($rowLogShare["DESTINATION"],$func->getConstant('dep_format',$conoracle));
			$arrLogShare["RESPONSE_MESSAGE"] = $rowLogShare["RESPONSE_MESSAGE"];
			$arrLogShare["UNIQE_ID"] = $rowLogShare["UNIQUE_ID"];
			$arrLogShare["STATUS_FLAG"] = $rowLogShare["STATUS_FLAG"];

			$arrayGroup[] = $arrLogShare;
		}
		$arrayResult["LOG_BUY_SHARE_DATA"] = $arrayGroup;
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