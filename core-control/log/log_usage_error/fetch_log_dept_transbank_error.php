<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepttransbankerror',$conoracle)){
		$arrayGroup = array();
		$fetchTranfertError = $conoracle->prepare("SELECT
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
			$arrLogTranfertError["ID_DEPTRANSBANKERR"] = $rowLogTranferError["ID_DEPTRANSBANKERR"];
			$arrLogTranfertError["MEMBER_NO"] = $rowLogTranferError["MEMBER_NO"];
			$arrLogTranfertError["CHANNEL"] = $rowLogTranferError["CHANNEL"];
			$arrLogTranfertError["TRANSACTION_DATE"] =  $lib->convertdate($rowLogTranferError["TRANSACTION_DATE"],'d m Y',true); 
			$arrLogTranfertError["DEVICE_NAME"] = $rowLogTranferError["DEVICE_NAME"];
			$arrLogTranfertError["AMT_TRANSFER"] = $rowLogTranferError["AMT_TRANSFER"];
			$arrLogTranfertError["AMT_TRANSFER_FORMAT"] = number_format($rowLogTranferError["AMT_TRANSFER"],2);
			$arrLogTranfertError["SIGMA_KEY"] = $rowLogTranferError["SIGMA_KEY"];
			$arrLogTranfertError["RESPONSE_CODE"] = $rowLogTranferError["RESPONSE_CODE"];
			$arrLogTranfertError["RESPONSE_MESSAGE"] = $rowLogTranferError["RESPONSE_MESSAGE"];
			
			$arrayGroup[] = $arrLogTranfertError;
		}
		$arrayResult["LOG_TRANFER_ERROR_DATA"] = $arrayGroup;
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