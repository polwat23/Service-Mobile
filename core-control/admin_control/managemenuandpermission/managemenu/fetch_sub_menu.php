<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conoracle->prepare("SELECT id_submenu, menu_name, menu_status,id_coremenu,id_menuparent,menu_order
												FROM coresubmenu
												WHERE menu_status <>'-9' AND id_menuparent !=0
												ORDER BY menu_order ASC");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_SUbMENU"] = $rowMenuMobile["ID_SUBMENU"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["MENU_NAME"];
			$arrGroupMenu["MENU_STATUS"] = $rowMenuMobile["MENU_STATUS"];
			$arrGroupMenu["ID_MENUPARENT"] = $rowMenuMobile["ID_MENUPARENT"];
			$arrGroupMenu["MENU_ORDER"] = $rowMenuMobile["MENU_ORDER"];
			$arrGroupMenu["ID_COREMENU"] = $rowMenuMobile["ID_COREMENU"];
			$arrayGroup[] = $arrGroupMenu;
		}
		$arrayResult["MENU_ALL"] = $arrayGroup;
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