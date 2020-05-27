<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loginlog')){
		$arrayGroup = array();
		$fetchBindAccountLog = $conmysql->prepare("SELECT id_logunbindaccount,member_no,id_userlogin,unbind_status,attempt_unbind_date,
														response_code,response_message,id_bindaccount,data_unbind_error,query_error,query_flag
													FROM logunbindaccount");
		$fetchBindAccountLog->execute();
		while($rowBindAccountLog = $fetchBindAccountLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBindAccountLog = array();
			$arrGroupBindAccountLog["ID_LOGUNBINDACCOUNT"] = $rowBindAccountLog["id_logunbindaccount"];
			$arrGroupBindAccountLog["MEMBER_NO"] = $rowBindAccountLog["member_no"];
			$arrGroupBindAccountLog["UNBIND_STATUS"] = $rowBindAccountLog["unbind_status"];
			$arrGroupBindAccountLog["RESPONSE_CODE"] = $rowBindAccountLog["response_code"];
			$arrGroupBindAccountLog["ATTEMPT_UNBIND_DATE"] =  $lib->convertdate($rowBindAccountLog["attempt_unbind_date"],'d m Y',true); 
			$arrGroupBindAccountLog["RESPONSE_MESSAGE"] = $rowBindAccountLog["response_message"];
			
			$arrGroupBindAccountLog["ID_BIND_ACCOUNT"] = $rowBindAccountLog["id_bindaccount"];
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO"] = $rowBindAccountLog["coop_account_no"];
			$arrGroupBindAccountLog["DATA_UNBIND_ERROR"] = $rowBindAccountLog["data_unbind_error"];
			$arrGroupBindAccountLog["QUERY_ERROR"] = $rowBindAccountLog["query_error"];
			$arrGroupBindAccountLog["QUERY_FLAG"] = $rowBindAccountLog["query_flag"];
			
			$arrayGroup[] = $arrGroupBindAccountLog;
		}
		$arrayResult["UNBIND_ACCOUNT_LOG"] = $arrayGroup;
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