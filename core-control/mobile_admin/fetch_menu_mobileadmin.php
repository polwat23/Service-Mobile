<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && $lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'mobileadmin',$conmysql)){
		$arrayGroup = array();
		$fetchMenu = $conmysql->prepare("SELECT admin_menu_name,page_name,id_adminmenu
										 FROM mobileadminmenu WHERE id_menuparent = 0 and id_coremenu = 2 and admin_menu_status = '1' ORDER BY adminmenu_order ASC");
		$fetchMenu->execute();
		while($rowMenu = $fetchMenu->fetch()){
			$arrGroupRootMenu = array();
			$arrGroupRootMenu["ROOT_MENU_NAME"] = $rowMenu["admin_menu_name"];
			$arrGroupRootMenu["ROOT_PATH"] = $rowMenu["page_name"];
			$fetchMenuAdmin = $conmysql->prepare("SELECT admin_menu_name,page_name
												FROM mobileadminmenu WHERE admin_menu_status = '1' and id_menuparent = :id_coremenu ORDER BY adminmenu_order ASC");
			$fetchMenuAdmin->execute([':id_coremenu' => $rowMenu["id_adminmenu"]]);
			while($rowMobileAdminMenu = $fetchMenuAdmin->fetch()){
				$arrayGroupMobileAdmin = array();
				$arrayGroupMobileAdmin["MOBILEADMIN_MENU_NAME"] = $rowMobileAdminMenu["admin_menu_name"];
				$arrayGroupMobileAdmin["MOBILEADMIN_PAGE_NAME"] = '/mobileadmin/'.$rowMenu["page_name"].'/'.$rowMobileAdminMenu["page_name"];
				($arrGroupRootMenu["MOBILEADMIN_MENU"])[] = $arrayGroupMobileAdmin;
			}
			$arrayGroup[] = $arrGroupRootMenu;
		}
		$arrayResult["MENU_MOBILEADMIN"] = $arrayGroup;
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