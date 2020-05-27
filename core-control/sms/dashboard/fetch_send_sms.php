<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$fetchSmsSend = $conmysql->prepare("SELECT
												COUNT(MEMBER_NO) AS C_NAME,
												DATE_FORMAT(send_date, '%m') AS MONTH
											FROM
												smslogwassent
											WHERE
												send_date <= DATE_SUB(
													send_date,
													INTERVAL -6 MONTH
												)
											GROUP BY
												DATE_FORMAT(send_date, '%m')");
		$fetchSmsSend->execute();
		while($rowSMSsend = $fetchSmsSend->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSystemSendSMS = array();
			$arrGroupSystemSendSMS["MONTH"] = $rowSMSsend["MONTH"];;
			$arrGroupSystemSendSMS["AMT"] = $rowSMSsend["C_NAME"];
			$arrayGroup[] = $arrGroupSystemSendSMS;
		}
					
		$arrayResult["SYSTEM_SEND_SMS_DATA"] = $arrayGroup;
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