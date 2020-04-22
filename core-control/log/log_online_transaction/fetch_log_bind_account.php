<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logunbindaccount')){
		$arrayGroup = array();
		$fetchBindAccountLog = $conmysql->prepare("SELECT
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
		while($rowBindAccountLog = $fetchBindAccountLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBindAccountLog = array();
			$arrGroupBindAccountLog["ID_BINDACCOUNT"] = $rowBindAccountLog["id_bindaccount"];
			$arrGroupBindAccountLog["MEMBER_NO"] = $rowBindAccountLog["member_no"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NAME"] = $rowBindAccountLog["bank_account_name"];
			$arrGroupBindAccountLog["BIND_STATUS"] = $rowBindAccountLog["bindaccount_status"];
			$arrGroupBindAccountLog["CONSENT_DATE"] =  $rowBindAccountLog["consent_date"]==null?"-":$lib->convertdate($rowBindAccountLog["consent_date"],'d m Y',true); 
			$arrGroupBindAccountLog["BIND_DATE"] =  $rowBindAccountLog["bind_date"]==null?"-":$lib->convertdate($rowBindAccountLog["bind_date"],'d m Y',true); 
			$arrGroupBindAccountLog["DEVICE_NAME"] = $rowBindAccountLog["device_name"];
			$arrGroupBindAccountLog["IP_ADDRESS"] = $rowBindAccountLog["ip_address"];
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($rowBindAccountLog["deptaccount_no_coop"],$func->getConstant('dep_format'));
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO"] = $rowBindAccountLog["deptaccount_no_coop"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NO"] = $rowBindAccountLog["deptaccount_no_bank"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NO_FORMAT"] = $lib->formataccount( $rowBindAccountLog["deptaccount_no_bank"],$func->getConstant('dep_format'));
			$arrayGroup[] = $arrGroupBindAccountLog;
		}
		$arrayResult["BIND_ACCOUNT_LOG"] = $arrayGroup;
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