<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu',$conmysql)){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT id_menu, menu_name, menu_status FROM gcmenu 
											  WHERE menu_status <>'9' AND menu_parent NOT IN ('-1','-2','-8','-9')  
											  ORDER BY menu_order ASC ");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch()){
			$arrGroupMenuMobile = array();
			$arrGroupMenuMobile["ID_MENU"] = $rowMenuMobile["id_menu"];
			$arrGroupMenuMobile["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenuMobile["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			$arrayGroup[] = $arrGroupMenuMobile;
		}
		$arrayResult["MENU_MOBILE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>