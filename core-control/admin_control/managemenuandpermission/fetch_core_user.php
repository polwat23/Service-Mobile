<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conoracle->prepare("SELECT user_name as USERNAME FROM amsecusers WHERE user_name NOT IN('admina')");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreSubMenu = array();
			$arrGroupCoreSubMenu["USERNAME"] = $rowCoreSubMenu["USERNAME"];
			$arrayGroup[] = $arrGroupCoreSubMenu;
		}
		$arrayResult["CORE_USER"] = $arrayGroup;
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