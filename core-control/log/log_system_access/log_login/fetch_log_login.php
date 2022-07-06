<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loglogin',$conoracle)){
		$arrayGroup = array();
		$fetchfetchLoginLog = $conoracle->prepare("SELECT
																				g.id_userlogin,
																				g.member_no,
																				g.device_name,
																				g.channel,
																				g.login_date,
																				g.logout_date,
																				g.is_login,
																				g.unique_id,
																				g.status_firstapp,
																				k.ip_address
																			FROM
																				gcuserlogin g
																			INNER JOIN gctoken k ON
																				k.id_token = g.id_token
																			ORDER BY g.login_date DESC");
		$fetchfetchLoginLog->execute();
		while($rowLoginLog = $fetchfetchLoginLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLoginLog = array();
			$arrGroupLoginLog["ID_USERLOGIN"] = $rowLoginLog["ID_USERLOGIN"];
			$arrGroupLoginLog["MEMBER_NO"] = $rowLoginLog["MEMBER_NO"];
			$arrGroupLoginLog["DEVICE_NAME"] = $rowLoginLog["DEVICE_NAME"];
			$arrGroupLoginLog["CHANNEL"] = $rowLoginLog["CHANNEL"];
			$arrGroupLoginLog["LOGIN_DATE"] =  $lib->convertdate($rowLoginLog["LOGIN_DATE"],'d m Y',true); 
			$arrGroupLoginLog["LOGOUT_DATE"] =  isset($rowLoginLog["LOGOUT_DATE"]) ? $lib->convertdate($rowLoginLog["LOGOUT_DATE"],'d m Y',true) : null;
			$arrGroupLoginLog["IS_LOGIN"] = $rowLoginLog["IS_LOGIN"];
			$arrGroupLoginLog["IP_ADDRESS"] = $rowLoginLog["IP_ADDRESS"];
			
			$arrayGroup[] = $arrGroupLoginLog;
		}
		$arrayResult["LOGINLOG_DATA"] = $arrayGroup;
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