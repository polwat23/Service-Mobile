<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logunbindaccount',$conoracle)){
		$arrayGroup = array();
		$fetchBindAccountLog = $conoracle->prepare("SELECT
																				bind.id_bindaccount,
																				bind.sigma_key,
																				bind.member_no,
																				bind.bank_account_name,
																				bind.deptaccount_no_coop,
																				bind.deptaccount_no_bank,
																				bind.mobile_no,
																				bind.consent_date,
																				bind.bind_date,
																				bind.bindaccount_status,
																				token.device_name,
																				token.ip_address
																			FROM
																				gcbindaccount bind
																			LEFT JOIN gctoken token ON
																				token.id_token = bind.id_token
																			ORDER BY bind.update_date DESC");
		$fetchBindAccountLog->execute();
		$formatDept = $func->getConstant('dep_format',$conoracle);
		while($rowBindAccountLog = $fetchBindAccountLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBindAccountLog = array();
			$arrGroupBindAccountLog["ID_BINDACCOUNT"] = $rowBindAccountLog["ID_BINDACCOUNT"];
			$arrGroupBindAccountLog["MEMBER_NO"] = $rowBindAccountLog["MEMBER_NO"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NAME"] = $rowBindAccountLog["BANK_ACCOUNT_NAME"];
			$arrGroupBindAccountLog["BIND_STATUS"] = $rowBindAccountLog["BINDACCOUNT_STATUS"];
			$arrGroupBindAccountLog["CONSENT_DATE"] =  $rowBindAccountLog["CONSENT_DATE"]==null?"-":$lib->convertdate($rowBindAccountLog["CONSENT_DATE"],'d m Y',true); 
			$arrGroupBindAccountLog["BIND_DATE"] =  $rowBindAccountLog["BIND_DATE"]==null?"-":$lib->convertdate($rowBindAccountLog["BIND_DATE"],'d m Y',true); 
			$arrGroupBindAccountLog["DEVICE_NAME"] = $rowBindAccountLog["DEVICE_NAME"];
			$arrGroupBindAccountLog["MOBILE_NO"] = $rowBindAccountLog["MOBILE_NO"];
			$arrGroupBindAccountLog["IP_ADDRESS"] = $rowBindAccountLog["IP_ADDRESS"];
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($rowBindAccountLog["DEPTACCOUNT_NO_COOP"],$formatDept);
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO"] = $rowBindAccountLog["DEPTACCOUNT_NO_COOP"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NO"] = $rowBindAccountLog["DEPTACCOUNT_NO_BANK"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NO_FORMAT"] = $lib->formataccount( $rowBindAccountLog["DEPTACCOUNT_NO_BANK"],$formatDept);
			$arrayGroup[] = $arrGroupBindAccountLog;
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