<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT id_menu, menu_name, menu_status,menu_channel FROM gcmenu 
											  WHERE menu_status <>'-99' AND menu_parent NOT IN ('-1','-2','-8','-9')  
											  ORDER BY menu_order ASC ");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch()){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_MENU"] = $rowMenuMobile["id_menu"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenu["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			$arrGroupMenu["MENU_CHANNEL"] = $rowMenuMobile["menu_channel"];
			$arrayGroup[] = $arrGroupMenu;
		}
		$arrayResult["MENU_ALL"] = $arrayGroup;
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