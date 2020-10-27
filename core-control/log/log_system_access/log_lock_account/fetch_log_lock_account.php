<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loglockaccount')){
		$arrayGroup = array();
		$fetchLogLockAccount = $conmysql->prepare("SELECT
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
			$arrGroupLogLockAcc["MEMBER_NO"] = $rowLogLockAccount["member_no"];
			$arrGroupLogLockAcc["DEVICE_NAME"] = $rowLogLockAccount["device_name"];
			$arrGroupLogLockAcc["UNIQUE_ID"] = $rowLogLockAccount["unique_id"];
			$arrGroupLogLockAcc["LOCK_DATE"] =  isset($rowLogLockAccount["lock_date"]) ? $lib->convertdate($rowLogLockAccount["lock_date"],'d m Y',true) : null;

			$arrayGroup[] = $arrGroupLogLockAcc;
		}
		$arrayResult["LOG_LOCK_ACCOUNT_DATA"] = $arrayGroup;
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