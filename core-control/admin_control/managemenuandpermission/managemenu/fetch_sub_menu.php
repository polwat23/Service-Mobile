<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT coresubmenu.id_submenu, coresubmenu.menu_name, coresubmenu.menu_status, coremenu.coremenu_name
											   FROM coresubmenu
											   INNER JOIN coremenu
											   ON coresubmenu.id_coremenu = coresubmenu.id_coremenu
											   WHERE menu_status <>'-9' AND id_menuparent !=0
ORDER BY menu_order ASC");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch()){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_SUbMENU"] = $rowMenuMobile["id_submenu"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenu["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			$arrGroupMenu["COREMENU_NAME"] = $rowMenuMobile["coremenu_name"];
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