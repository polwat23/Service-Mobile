<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conoracle->prepare("SELECT am.DESCRIPTION,am.USER_NAME,hr.DEPTGRP_CODE FROM amsecusers am 
										LEFT JOIN hremployee hr ON am.user_id = hr.emp_no");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreUser = array();
			$arrGroupCoreUser["USERNAME"] = $rowCoreSubMenu["USER_NAME"];
			$arrGroupCoreUser["ID_SECTION_SYSTEM"] = $rowCoreSubMenu["DEPTGRP_CODE"];
			$arrGroupCoreUser["SECTION_SYSTEM"] = $rowCoreSubMenu["DEPTGRP_CODE"];
			$arrGroupCoreUser["SYSTEM_ASSIGN"] = $rowCoreSubMenu["DESCRIPTION"];
			$arrayGroup[] = $arrGroupCoreUser;
		}
		$arrayResult["CORE_USER"] = $arrayGroup;
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