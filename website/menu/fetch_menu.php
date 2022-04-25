<?php
require_once('../autoload.php');

	$arrayGroup = array();
	$submenu = array();
	$allMenu = array();
	$fetchMenu = $conmysql->prepare("SELECT
											id_menu,
											menu_name,
											menu_status,
											page_name,
											menu_order,
											id_menuparent,
											type 
										FROM webcoopmenu
										WHERE id_menuparent = '0' AND menu_status ='1'
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
										WHERE id_menuparent = :id_menu AND menu_status = '1'
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
	
	$fetchAllMenu = $conmysql->prepare("SELECT
											sub.id_menu,
											sub.menu_name,
											sub.menu_status,
											sub.page_name,
											sub.menu_order,
											sub.id_menuparent,
											parent.page_name AS parent_page,
											sub.type
										FROM
											webcoopmenu sub
										LEFT JOIN webcoopmenu parent ON
											sub.id_menuparent = parent.id_menu
										WHERE
											sub.menu_status = '1'
										ORDER BY
											menu_order
									");
	$fetchAllMenu->execute();
	while($rowAllMenu = $fetchAllMenu->fetch(PDO::FETCH_ASSOC)){
		$arrAllMenu["PAGE"] = $rowAllMenu["parent_page"]."/".$rowAllMenu["page_name"]."/";
		$arrAllMenu["ID_MENU"] = $rowAllMenu["id_menu"];
		$arrAllMenu["MENU_NAME"] = $rowAllMenu["menu_name"];
		$arrAllMenu["MENU_STATUS"] = $rowAllMenu["menu_status"];
		$arrAllMenu["PAGE_NAME"] = $rowAllMenu["page_name"];
		$arrAllMenu["MENU_ORDER"] = $rowAllMenu["page_name"];
		$arrAllMenu["ID_MENUPARENT"] = $rowAllMenu["id_menuparent"];
		$arrAllMenu["PARENT_PAGE"] = $rowAllMenu["parent_page"];
		$arrAllMenu["TYPE"] = $rowAllMenu["type"];
		$allMenu[] = $arrAllMenu;	
	}
	
	$fetcUrlLoginWeb = $conmysql->prepare("SELECT web_url FROM webcoopprofile");
	$fetcUrlLoginWeb->execute();
	$urlLoginWeb = $fetcUrlLoginWeb->fetch(PDO::FETCH_ASSOC);
	
	
	$arrayResult["MENU_DATA"] = $arrayGroup;
	$arrayResult["WEBLOGIN"] = $urlLoginWeb["web_url"]??NULL;
	$arrayResult["ALL_MENU_DATA"] = $allMenu;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

?>