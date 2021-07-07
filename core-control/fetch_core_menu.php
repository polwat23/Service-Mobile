<?php
require_once('autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	if($payload["section_system"] == "root" || $payload["section_system"] == "root_test"){
		$fetchMenu = $conoracle->prepare("SELECT id_coremenu,coremenu_name,coremenu_iconpath,coremenu_status,
										coremenu_desc,coremenu_colorbanner,root_path,coremenu_colortext
										FROM coremenu
										WHERE coremenu_parent = '0' ORDER BY coremenu_order ASC");
		$fetchMenu->execute();
	}else{
		$fetchMenu = $conoracle->prepare("SELECT cm.id_coremenu,cm.coremenu_name,cm.coremenu_iconpath,cm.coremenu_status,
										cm.coremenu_desc,cm.coremenu_colorbanner,cm.root_path,cm.coremenu_colortext,cpm.id_permission_menu
										FROM coremenu cm LEFT JOIN corepermissionmenu cpm ON cm.id_coremenu = cpm.id_coremenu and cpm.is_use = '1'
										WHERE cm.coremenu_status NOT IN('0','-99') and cm.coremenu_parent = '0' and cpm.username = :username
										ORDER BY cm.coremenu_order ASC");
		$fetchMenu->execute([':username' => $payload["username"]]);
	}
	while($rowMenu = $fetchMenu->fetch(PDO::FETCH_ASSOC)){
		if($payload["section_system"] == "root" || $payload["section_system"] == "root_test"){
			$arrMenu = array();
			$arrMenu["MENU_NAME"] = $rowMenu["COREMENU_NAME"];
			$arrMenu["MENU_ICONPATH"] = $rowMenu["COREMENU_ICONPATH"];
			$arrMenu["MENU_ID"] = $rowMenu["ID_COREMENU"];
			$arrMenu["MENU_STATUS"] = $rowMenu["COREMENU_STATUS"];
			$arrMenu["MENU_DESC"] = $rowMenu["COREMENU_DESC"];
			$arrMenu["MENU_COLOR_BANNER"] = $rowMenu["COREMENU_COLORBANNER"];
			$arrMenu["MENU_TEXT_COLOR"] = $rowMenu["COREMENU_COLORTEXT"];
			$arrMenu["ROOT_PATH"] = $rowMenu["ROOT_PATH"];
			$arrayGroup[] = $arrMenu;
		}else{
			$checkPermission = $conoracle->prepare("SELECT cpm.id_permission_submenu FROM corepermissionsubmenu cpm LEFT JOIN coresubmenu csm
													ON cpm.id_submenu = csm.id_submenu
													WHERE cpm.id_permission_menu = :id_permission_menu and cpm.is_use = '1' and csm.menu_status = '1'");
			$checkPermission->execute([':id_permission_menu' => $rowMenu["id_permission_menu"]]);
			$arrMenu = array();
			$arrMenu["MENU_NAME"] = $rowMenu["COREMENU_NAME"];
			$arrMenu["MENU_ICONPATH"] = $rowMenu["COREMENU_ICONPATH"];
			$arrMenu["MENU_ID"] = $rowMenu["ID_COREMENU"];
			$arrMenu["MENU_STATUS"] = $rowMenu["COREMENU_STATUS"];
			$arrMenu["MENU_DESC"] = $rowMenu["COREMENU_DESC"];
			$arrMenu["MENU_COLOR_BANNER"] = $rowMenu["COREMENU_COLORBANNER"];
			$arrMenu["MENU_TEXT_COLOR"] = $rowMenu["COREMENU_COLORTEXT"];
			$arrMenu["ROOT_PATH"] = $rowMenu["ROOT_PATH"];
			$arrayGroup[] = $arrMenu;
			
		}
	}
	$arrayResult["MENU"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	require_once('../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../include/exit_footer.php');
	
}
?>