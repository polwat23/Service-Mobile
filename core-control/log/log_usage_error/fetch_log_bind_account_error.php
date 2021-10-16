<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logbindaccounterror',$conoracle)){
		$arrayGroup = array();
		$fetchLogBindAccountError = $conoracle->prepare("SELECT bind.id_logbindaccount,
															bind.member_no,
															bind.bind_status,
															bind.attempt_bind_date,
															bind.response_code,	
															bind.response_message,
															bind.data_bind_error,
															bind.query_error,
															bind.query_flag,
															bind.coop_account_no,
															login.device_name,
															login.channel
													FROM logbindaccount bind
													INNER JOIN gcuserlogin login
													ON login.id_userlogin = bind.id_userlogin
													WHERE bind.bind_status !=1 ORDER BY bind.attempt_bind_date DESC");
		$fetchLogBindAccountError->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowLogBindAccountError = $fetchLogBindAccountError->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogBindAccountError = array();
			$arrGroupLogBindAccountError["ID_LOGBINDACCOUNT"] = $rowLogBindAccountError["ID_LOGBINDACCOUNT"];
			$arrGroupLogBindAccountError["MEMBER_NO"] = $rowLogBindAccountError["MEMBER_NO"];
			$arrGroupLogBindAccountError["CHANNEL"] = $rowLogBindAccountError["CHANNEL"];
			$arrGroupLogBindAccountError["BIND_STATUS"] = $rowLogBindAccountError["BIND_STATUS"];
			$arrGroupLogBindAccountError["RESPONSE_CODE"] = $rowLogBindAccountError["RESPONSE_CODE"];
			$arrGroupLogBindAccountError["DEVICE_NAME"] = $rowLogBindAccountError["DEVICE_NAME"];
			$arrGroupLogBindAccountError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogBindAccountError["ATTEMPT_BIND_DATE"],'d m Y',true); 
			$arrGroupLogBindAccountError["RESPONSE_MESSAGE"] = $rowLogBindAccountError["RESPONSE_MESSAGE"];
			$arrGroupLogBindAccountError["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogBindAccountError["COOP_ACCOUNT_NO"],$formatDept);
			$arrGroupLogBindAccountError["COOP_ACCOUNT_NO"] = $rowLogBindAccountError["COOP_ACCOUNT_NO"];
			$arrGroupLogBindAccountError["DATA_BIND_ERROR"] = $rowLogBindAccountError["DATA_BIND_ERROR"];
			$arrGroupLogBindAccountError["QUERY_ERROR"] = $rowLogBindAccountError["QUERY_ERROR"];
			$arrGroupLogBindAccountError["QUERY_FLAG"] = $rowLogBindAccountError["QUERY_FLAG"];
			
			$arrayGroup[] = $arrGroupLogBindAccountError;
		}
		$arrayResult["BIND_ACCOUNT_LOG"] = $arrayGroup;
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