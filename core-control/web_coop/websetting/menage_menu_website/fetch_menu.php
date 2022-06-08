<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$submenu = array();
	
	$fetchMenu = $conmysql->prepare("SELECT
											id_menu,
											menu_name,
											menu_status,
											page_name,
											menu_order,
											id_menuparent,
											type 
										FROM webcoopmenu
										WHERE id_menuparent = '0'
										ORDER BY menu_order");
	$fetchMenu->execute();
	while($rowParentMemu = $fetchMenu->fetch(PDO::FETCH_ASSOC)){
		$arrParentMenu["ID_MENU"] = $rowParentMemu["id_menu"];
		$arrParentMenu["MENU_NAME"] = $rowParentMemu["menu_name"];
		$arrParentMenu["MENU_STATUS"] = $rowParentMemu["menu_status"];
		$arrParentMenu["PAGE_NAME"] = $rowParentMemu["page_name"];
		$arrParentMenu["MENU_ORDER"] = $rowParentMemu["menu_order"];
		$arrParentMenu["ID_MENUPARENT"] = $rowParentMemu["id_menuparent"];
		$arrParentMenu["TYPE"] = $rowParentMemu["type"];

		
			$fetchSbuMenu = $conmysql->prepare("SELECT
											id_menu,
											menu_name,
											menu_status,
											page_name,
											menu_order,
											id_menuparent,
											type 
										FROM webcoopmenu
										WHERE id_menuparent = :id_menu
										ORDER BY menu_order");
			$fetchSbuMenu->execute([
				':id_menu' =>  $rowParentMemu["id_menu"]
			]);
			$submenu = array();
			while($rowSubMemu = $fetchSbuMenu->fetch(PDO::FETCH_ASSOC)){
				$arrSubMenu["ID_MENU"] = $rowSubMemu["id_menu"];
				$arrSubMenu["MENU_NAME"] = $rowSubMemu["menu_name"];
				$arrSubMenu["MENU_STATUS"] = $rowSubMemu["menu_status"];
				$arrSubMenu["PAGE_NAME"] = $rowSubMemu["page_name"];
				$arrSubMenu["MENU_ORDER"] = $rowSubMemu["page_name"];
				$arrSubMenu["ID_MENUPARENT"] = $rowSubMemu["id_menuparent"];
				$arrSubMenu["PAGE"] = $rowParentMemu["page_name"]."/".$rowSubMemu["page_name"]."/";
				$arrSubMenu["TYPE"] = $rowSubMemu["type"];
				$submenu[] = $arrSubMenu;	
			}
		$arrParentMenu["SUB_MENU"] = $submenu;
		$arrayGroup[] = $arrParentMenu;
		
	}
	$arrayResult["MENU_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>