<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepttransbankerror')){
		$arrayGroup = array();
		$fetchTranfertError = $conmysql->prepare("SELECT
													db.id_deptransbankerr,
													db.member_no,
													db.transaction_date,
													db.sigma_key,
													db.amt_transfer,
													db.response_code,
													db.response_message,
													log.device_name,
													log.channel
												FROM
													logdepttransbankerror db
												INNER JOIN gcuserlogin log
												ON log.id_userlogin = db.id_userlogin");
		$fetchTranfertError->execute();
		while($rowLogTranferError = $fetchTranfertError->fetch(PDO::FETCH_ASSOC)){
			$arrLogTranfertError = array();
			$arrLogTranfertError["ID_DEPTRANSBANKERR"] = $rowLogTranferError["id_deptransbankerr"];
			$arrLogTranfertError["MEMBER_NO"] = $rowLogTranferError["member_no"];
			$arrLogTranfertError["CHANNEL"] = $rowLogTranferError["channel"];
			$arrLogTranfertError["TRANSACTION_DATE"] =  $lib->convertdate($rowLogTranferError["transaction_date"],'d m Y',true); 
			$arrLogTranfertError["DEVICE_NAME"] = $rowLogTranferError["device_name"];
			$arrLogTranfertError["AMT_TRANSFER"] = $rowLogTranferError["amt_transfer"];
			$arrLogTranfertError["AMT_TRANSFER_FORMAT"] = number_format($rowLogTranferError["amt_transfer"],2);
			$arrLogTranfertError["SIGMA_KEY"] = $rowLogTranferError["sigma_key"];
			//$arrLogTranfertError["TYPE_REQUEST"] = $rowLogTranferError["type_request"];
		
			//$arrLogTranfertError["TRANSFER_FLAG"] = $rowLogTranferError["transfer_flag"];
			//$arrLogTranfertError["DESTINATION"] = $rowLogTranferError["destination"];
			//$arrLogTranfertError["DESTINATION_NO_FORMAT"]= $lib->formataccount($rowLogTranferError["destination"],$func->getConstant('dep_format'));
			//$arrLogTranfertError["DEPTACCOUNT_NO"] = $rowLogTranferError["deptaccount_no"];
			//$arrLogTranfertError["DEPTACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogTranferError["deptaccount_no"],$func->getConstant('dep_format'));
			$arrLogTranfertError["RESPONSE_CODE"] = $rowLogTranferError["response_code"];
			$arrLogTranfertError["RESPONSE_MESSAGE"] = $rowLogTranferError["response_message"];
			
			$arrayGroup[] = $arrLogTranfertError;
		}
		$arrayResult["LOG_TRANFER_ERROR_DATA"] = $arrayGroup;
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