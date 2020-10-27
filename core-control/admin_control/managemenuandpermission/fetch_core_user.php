<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conoracle->prepare("SELECT USER_NAME FROM amsecusers");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreSubMenu = array();
			$arrGroupCoreSubMenu["USERNAME"] = $rowCoreSubMenu["USER_NAME"];
			$arrayGroup[] = $arrGroupCoreSubMenu;
		}
		$arrayResult["CORE_USER"] = $arrayGroup;
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