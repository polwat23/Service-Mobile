<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu',$conoracle)){
		$arrayGroup = array();
		$fetchMenuMobile = $conoracle->prepare("SELECT id_menu, menu_name,menu_name_en, menu_status,menu_channel,menu_parent FROM gcmenu 
											  WHERE menu_status <> '-9' AND menu_parent IN(0,18,19,-9,-8,-1,57)
											  ORDER BY menu_order ASC ");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_MENU"] = $rowMenuMobile["ID_MENU"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["MENU_NAME"];
			$arrGroupMenu["MENU_NAME_EN"] = $rowMenuMobile["MENU_NAME_EN"];
			$arrGroupMenu["MENU_STATUS"] = $rowMenuMobile["MENU_STATUS"];
			$arrGroupMenu["MENU_PARENT"] = $rowMenuMobile["MENU_PARENT"];
			$arrGroupMenu["MENU_CHANNEL"] = $rowMenuMobile["MENU_CHANNEL"];
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