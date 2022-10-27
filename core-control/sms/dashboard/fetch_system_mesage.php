<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		/*$fetchSMSsystemSend = $conoracle->prepare("SELECT
													COUNT(MEMBER_NO) AS C_NAME,
													TO_CHAR(receive_date, 'MM') AS MONTH
												FROM
													gchistory
												WHERE
													 TO_CHAR(receive_date,'YYYYMMDD')  BETWEEN TO_CHAR(ADD_MONTHS(sysdate, -6), 'YYYYMMDD') and TO_CHAR(sysdate, 'YYYYMMDD')
												GROUP BY receive_date ");
		$fetchSMSsystemSend->execute();
		while($rowSMSsendSytem = $fetchSMSsystemSend->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSystemSendSMS = array();
			$arrGroupSystemSendSMS["MONTH"] = $rowSMSsendSytem["MONTH"];;
			$arrGroupSystemSendSMS["AMT"] = $rowSMSsendSytem["C_NAME"];
			$arrayGroup[] = $arrGroupSystemSendSMS;
		}*/
					
		$arrayResult["SYSTEM_SEND_SMS_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>