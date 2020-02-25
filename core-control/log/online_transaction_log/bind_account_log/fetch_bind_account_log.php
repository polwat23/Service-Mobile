<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loginlog')){
		$arrayGroup = array();
		$fetchBindAccountLog = $conmysql->prepare("SELECT id_logbindaccount,member_no,bind_status,attempt_bind_date,response_code,
														response_message,coop_account_no,data_bind_error,query_error,query_flag
												  FROM logbindaccount");
		$fetchBindAccountLog->execute();
		while($rowBindAccountLog = $fetchBindAccountLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBindAccountLog = array();
			$arrGroupBindAccountLog["ID_LOGBINDACCOUNT"] = $rowBindAccountLog["id_logbindaccount"];
			$arrGroupBindAccountLog["MEMBER_NO"] = $rowBindAccountLog["member_no"];
			$arrGroupBindAccountLog["BIND_STATUS"] = $rowBindAccountLog["bind_status"];
			$arrGroupBindAccountLog["RESPONSE_CODE"] = $rowBindAccountLog["response_code"];
			$arrGroupBindAccountLog["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowBindAccountLog["attempt_bind_date"],'d m Y',true); 
			$arrGroupBindAccountLog["RESPONSE_MESSAGE"] = $rowBindAccountLog["response_message"];
			
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO"] = $rowBindAccountLog["coop_account_no"];
			$arrGroupBindAccountLog["DATA_BIND_ERROR"] = $rowBindAccountLog["data_bind_error"];
			$arrGroupBindAccountLog["QUERY_ERROR"] = $rowBindAccountLog["query_error"];
			$arrGroupBindAccountLog["QUERY_FLAG"] = $rowBindAccountLog["query_flag"];
			
			$arrayGroup[] = $arrGroupBindAccountLog;
		}
		$arrayResult["LOGINLOG_DATA"] = $arrayGroup;
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