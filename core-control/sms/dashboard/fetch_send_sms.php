<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$fetchSmsSend = $conoracle->prepare("SELECT
												COUNT(MEMBER_NO) AS C_NAME,
												TO_CHAR(send_date, 'MM') AS MONTH
											FROM
												smslogwassent
											WHERE
											TO_CHAR(send_date, 'YYYYMMDD')  BETWEEN TO_CHAR(ADD_MONTHS(sysdate,-6), 'YYYYMMDD') and TO_CHAR(sysdate, 'YYYYMMDD')
											GROUP BY send_date");
		$fetchSmsSend->execute();
		while($rowSMSsend = $fetchSmsSend->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSystemSendSMS = array();
			$arrGroupSystemSendSMS["MONTH"] = $rowSMSsend["MONTH"];;
			$arrGroupSystemSendSMS["AMT"] = $rowSMSsend["C_NAME"];
			$arrayGroup[] = $arrGroupSystemSendSMS;
		}
					
		$arrayResult["SYSTEM_SEND_SMS_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>