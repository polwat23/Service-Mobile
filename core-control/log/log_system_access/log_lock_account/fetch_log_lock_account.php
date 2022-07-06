<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loglockaccount',$conoracle)){
		$arrayGroup = array();
		$fetchLogLockAccount = $conoracle->prepare("SELECT
																			member_no,
																			device_name,
																			unique_id,
																			lock_date
																		FROM
																			loglockaccount
																		ORDER BY lock_date DESC");
		$fetchLogLockAccount->execute();
		while($rowLogLockAccount = $fetchLogLockAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogLockAcc = array();
			$arrGroupLogLockAcc["MEMBER_NO"] = $rowLogLockAccount["MEMBER_NO"];
			$arrGroupLogLockAcc["DEVICE_NAME"] = $rowLogLockAccount["DEVICE_NAME"];
			$arrGroupLogLockAcc["UNIQUE_ID"] = $rowLogLockAccount["UNIQUE_ID"];
			$arrGroupLogLockAcc["LOCK_DATE"] =  isset($rowLogLockAccount["LOCK_DATE"]) ? $lib->convertdate($rowLogLockAccount["LOCK_DATE"],'d m Y',true) : null;

			$arrayGroup[] = $arrGroupLogLockAcc;
		}
		$arrayResult["LOG_LOCK_ACCOUNT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>