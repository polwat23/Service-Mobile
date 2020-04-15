<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositerror')){
		$arrayGroup = array();
		$fetchLogDepositError = $conmysql->prepare("SELECT  tb.id_deptransbankerr,tb.member_no,tb.transaction_date,tb.sigma_key,tb.amt_transfer,tb.response_code,
															tb.response_message,login.device_name,login.channel
													FROM logdepttransbankerror tb
													INNER JOIN gcuserlogin login
													ON login.id_userlogin = tb.id_userlogin");
		$fetchLogDepositError->execute();
		while($rowLogDepositError = $fetchLogDepositError->fetch(PDO::FETCH_ASSOC)){
			$arrLogDepositError = array();
			$arrLogDepositError["ID_DPTRANSBANKERR"] = $rowLogDepositError["id_deptransbankerr"];
			$arrLogDepositError["MEMBER_NO"] = $rowLogDepositError["member_no"];
			$arrLogDepositError["CHANNEL"] = $rowLogDepositError["channel"];
			$arrLogDepositError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogDepositError["transaction_date"],'d m Y',true); 
			$arrLogDepositError["DEVICE_NAME"] = $rowLogDepositError["device_name"];
			$arrLogDepositError["AMT_TRANSFER"] = $rowLogDepositError["amt_transfer"];
			
			$arrLogDepositError["SIGMA_KEY"] = $rowLogDepositError["sigma_key"];
			$arrLogDepositError["FEE_AMT"] = $rowLogDepositError["fee_amt"];
			//$arrLogDepositError["DEPTACCOUNT_NO"] = $rowLogDepositError["deptaccount_no"];
			//$arrLogDepositError["DEPTACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogDepositError["deptaccount_no"],$func->getConstant('dep_format'));
			$arrLogDepositError["RESPONSE_CODE"] = $rowLogDepositError["response_code"];
			$arrLogDepositError["RESPONSE_MESSAGE"] = $rowLogDepositError["response_message"];
			
	
			
			$arrayGroup[] = $arrLogDepositError;
		}
		$arrayResult["LOG_DEPOSIT_ERROR_DATA"] = $arrayGroup;
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