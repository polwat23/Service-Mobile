<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logunbindaccount',$conoracle)){
		$arrayGroup = array();
		$fetchLogBindAccount = $conoracle->prepare("SELECT
																				unbin.id_logunbindaccount,
																				unbin.member_no,
																				unbin.id_userlogin,
																				unbin.unbind_status,
																				unbin.attempt_unbind_date,
																				unbin.response_code,
																				unbin.response_message,
																				unbin.id_bindaccount,
																				unbin.data_unbind_error,
																				unbin.query_error,
																				unbin.query_flag,
																				login.channel,
																				login.device_name
																			FROM
																				logunbindaccount unbin
																			INNER JOIN gcuserlogin login ON
																				unbin.id_userlogin = login.id_userlogin  
																			ORDER BY unbin.attempt_unbind_date DESC");
		$fetchLogBindAccount->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowLogBindAccount = $fetchLogBindAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogBindAccount = array();
			$fetchBinAccountCoopNo = $conoracle->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank FROM gcbindaccount WHERE id_bindaccount = '$rowLogBindAccount[id_bindaccount]' ");
			$fetchBinAccountCoopNo -> execute();
			$coop_no=$fetchBinAccountCoopNo-> fetch(PDO::FETCH_ASSOC);
			
			$arrGroupLogBindAccount["ID_LOGUNBINDACCOUNT"] = $rowLogBindAccount["ID_LOGUNBINDACCOUNT"];
			$arrGroupLogBindAccount["MEMBER_NO"] = $rowLogBindAccount["MEMBER_NO"];
			$arrGroupLogBindAccount["UNBIND_STATUS"] = $rowLogBindAccount["UNBIND_STATUS"];
			$arrGroupLogBindAccount["RESPONSE_CODE"] = $rowLogBindAccount["RESPONSE_CODE"];
			$arrGroupLogBindAccount["ATTEMPT_UNBIND_DATE"] =  $lib->convertdate($rowLogBindAccount["ATTEMPT_UNBIND_DATE"],'d m Y',true); 
			$arrGroupLogBindAccount["RESPONSE_MESSAGE"] = $rowLogBindAccount["RESPONSE_MESSAGE"];
			$arrGroupLogBindAccount["DEVICE_NAME"] = $rowLogBindAccount["DEVICE_NAME"];
			$arrGroupLogBindAccount["CHANNEL"] = $rowLogBindAccount["CHANNEL"];
			$arrGroupLogBindAccount["ID_BIND_ACCOUNT"] = $rowLogBindAccount["ID_BINDACCOUNT"];
			$arrGroupLogBindAccount["COOP_ACCOUNT_NO"] = $coop_no["DEPTACCOUNT_NO_COOP"];
			$arrGroupLogBindAccount["BANK_ACCOUNT_NO"] = $coop_no["DEPTACCOUNT_NO_BANK"];
			$arrGroupLogBindAccount["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($coop_no["DEPTACCOUNT_NO_COOP"],$formatDept);
			$arrGroupLogBindAccount["BANK_ACCOUNT_NO_FORMAT"]= $lib->formataccount($coop_no["DEPTACCOUNT_NO_BANK"],$formatDept);
  		    $arrGroupLogBindAccount["DATA_UNBIND_ERROR"] = $rowLogBindAccount["DATA_UNBIND_ERROR"];
			$arrGroupLogBindAccount["QUERY_ERROR"] = $rowLogBindAccount["QUERY_ERROR"];
			$arrGroupLogBindAccount["QUERY_FLAG"] = $rowLogBindAccount["QUERY_FLAG"];
			
			$arrayGroup[] = $arrGroupLogBindAccount;
		}
		$arrayResult["UNBIND_ACCOUNT_LOG"] = $arrayGroup;
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