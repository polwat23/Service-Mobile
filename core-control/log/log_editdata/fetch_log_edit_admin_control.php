<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logeditadmincontrol',$conoracle)){
		$arrayGroup = array();
		$fetchLogAdminUsage = $conoracle->prepare("SELECT
																		menu_name,
																		username,
																		edit_date,
																		use_list,
																		details
																	FROM
																		logeditadmincontrol
																	ORDER BY edit_date DESC");
		$fetchLogAdminUsage->execute();
		while($rowLogAdminUsage = $fetchLogAdminUsage->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogAdminUsage = array();
			$arrGroupLogAdminUsage["MENU_NAME"] = $rowLogAdminUsage["MENU_NAME"];
			$arrGroupLogAdminUsage["USERNAME"] = $rowLogAdminUsage["USERNAME"];
			$arrGroupLogAdminUsage["DATE_USAGE"] =  $rowLogAdminUsage["EDIT_DATE"];
			$arrGroupLogAdminUsage["DATE_USAGE_FORMAT"] =  $lib->convertdate($rowLogAdminUsage["EDIT_DATE"],'d m Y',true); 
			$arrGroupLogAdminUsage["USE_LIST"] = $rowLogAdminUsage["USE_LIST"];
			$arrGroupLogAdminUsage["DETAILS"] = $rowLogAdminUsage["DETAILS"];
			
			$arrayGroup[] = $arrGroupLogAdminUsage;
		}
		$arrayResult["LOG_EDIT_MOBILEADMIN"] = $arrayGroup;
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