<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logapplicationuse',$conoracle)){
		$arrayGroup = array();
		$fetchApplicationUseLog = $conoracle->prepare("SELECT
																					l.id_loguseapp,
																					l.member_no,
																					l.id_userlogin,
																					l.access_date,
																					l.ip_address,
																					g.device_name,
																					g.login_date,
																					g.logout_date,
																					g.is_login
																				FROM
																					loguseapplication l
																				INNER JOIN gcuserlogin g ON
																					g.id_userlogin = l.id_userlogin
																				WHERE  TO_CHAR(l.access_date, 'YYYYMMDD')  BETWEEN TO_CHAR(ADD_MONTHS(sysdate,-3), 'YYYYMMDD') and TO_CHAR(sysdate, 'YYYYMMDD')
																					ORDER BY g.login_date DESC

");
		$fetchApplicationUseLog->execute();
		while($rowAppUseLog = $fetchApplicationUseLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupApplicationUseLog = array();
			$arrGroupApplicationUseLog["ID_USERLOGIN"] = $rowAppUseLog["ID_USERLOGIN"];
			$arrGroupApplicationUseLog["MEMBER_NO"] = $rowAppUseLog["MEMBER_NO"];
			$arrGroupApplicationUseLog["DEVICE_NAME"] = $rowAppUseLog["DEVICE_NAME"];
			$arrGroupApplicationUseLog["ACCESS_DATE"] =  $lib->convertdate($rowAppUseLog["ACCESS_DATE"],'d m Y',true); 
			$arrGroupApplicationUseLog["IS_LOGIN"] = $rowAppUseLog["IS_LOGIN"];
			$arrGroupApplicationUseLog["IP_ADDRESS"] = $rowAppUseLog["IP_ADDRESS"];
			
			$arrayGroup[] = $arrGroupApplicationUseLog;
		}
		$arrayResult["APP_USE_LOG_DATA"] = $arrayGroup;
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