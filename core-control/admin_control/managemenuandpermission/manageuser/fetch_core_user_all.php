<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conoracle->prepare("SELECT user_name as USERNAME
										FROM amsecusers");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreUser = array();
			$arrGroupCoreUser["USERNAME"] = $rowCoreSubMenu["USERNAME"];
			$arrGroupCoreUser["ID_SECTION_SYSTEM"] = 0;
			$arrGroupCoreUser["SECTION_SYSTEM"] = 'process';
			$arrGroupCoreUser["SYSTEM_ASSIGN"] = 'process';
			$arrGroupCoreUser["USER_STATUS"] = '1';
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