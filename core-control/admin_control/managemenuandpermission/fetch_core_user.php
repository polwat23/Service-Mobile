<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'setting','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT username FROM coreuser WHERE user_status = '1' and username NOT IN('dev@mode','salemode')");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch()){
			$arrGroupCoreSubMenu = array();
			$arrGroupCoreSubMenu["USERNAME"] = $rowCoreSubMenu["username"];
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