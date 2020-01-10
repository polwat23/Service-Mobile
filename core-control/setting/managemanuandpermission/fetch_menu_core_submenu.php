<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','title_menu'],$dataComing)){
	if($func->check_permission_core($payload,'setting','permissionmenu')){
		if(($dataComing['title_menu']=="sms")){
			$arrayGroup = array();
			$fetchMenuMobile = $conmysql->prepare("SELECT id_submenu, menu_name, menu_status  FROM `coresubmenu` 
													WHERE id_coremenu ='1' AND id_menuparent !='0'  
													ORDER BY id_menuparent ASC ");
			$fetchMenuMobile->execute();
			while($rowCoreSubMenu = $fetchMenuMobile->fetch()){
				$arrGroupCoreSubMenu = array();
				$arrGroupCoreSubMenu["ID_MENU"] = $rowCoreSubMenu["id_submenu"];
				$arrGroupCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
				$arrGroupCoreSubMenu["MENU_STATUS"] = $rowCoreSubMenu["menu_status"];
				$arrayGroup[] = $arrGroupCoreSubMenu;
			}
			$arrayResult["CORE_SUB_MENUE"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else if(($dataComing['title_menu']=="mobileadmin")){
			$arrayGroup = array();
			$fetchMenuMobile = $conmysql->prepare("SELECT id_submenu, menu_name, menu_status  FROM `coresubmenu` 
												WHERE id_coremenu ='2' AND id_menuparent !='0'  
												ORDER BY id_menuparent ASC ");
			$fetchMenuMobile->execute();
			while($rowCoreSubMenu = $fetchMenuMobile->fetch()){
				$arrGroupCoreSubMenu = array();
				$arrGroupCoreSubMenu["ID_MENU"] = $rowCoreSubMenu["id_submenu"];
				$arrGroupCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
				$arrGroupCoreSubMenu["MENU_STATUS"] = $rowCoreSubMenu["menu_status"];
				$arrayGroup[] = $arrGroupCoreSubMenu;
		}
			$arrayResult["CORE_SUB_MENUE"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else if(($dataComing['title_menu']=="line")){
			$arrayGroup = array();
			$fetchMenuMobile = $conmysql->prepare("SELECT id_submenu, menu_name, menu_status  FROM `coresubmenu` 
												WHERE id_coremenu ='3' AND id_menuparent !='0'  
												ORDER BY id_menuparent ASC ");
			$fetchMenuMobile->execute();
			while($rowCoreSubMenu = $fetchMenuMobile->fetch()){
				$arrGroupCoreSubMenu = array();
				$arrGroupCoreSubMenu["ID_MENU"] = $rowCoreSubMenu["id_submenu"];
				$arrGroupCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
				$arrGroupCoreSubMenu["MENU_STATUS"] = $rowCoreSubMenu["menu_status"];
				$arrayGroup[] = $arrGroupCoreSubMenu;
			}
			$arrayResult["CORE_SUB_MENUE"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else if(($dataComing['title_menu']=="log")){
			$arrayGroup = array();
			$fetchMenuMobile = $conmysql->prepare("SELECT id_submenu, menu_name, menu_status  FROM `coresubmenu` 
												WHERE id_coremenu ='4' AND id_menuparent !='0'  
												ORDER BY id_menuparent ASC ");
			$fetchMenuMobile->execute();
			while($rowCoreSubMenu = $fetchMenuMobile->fetch()){
				$arrGroupCoreSubMenu = array();
				$arrGroupCoreSubMenu["ID_MENU"] = $rowCoreSubMenu["id_submenu"];
				$arrGroupCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
				$arrGroupCoreSubMenu["MENU_STATUS"] = $rowCoreSubMenu["menu_status"];
				$arrayGroup[] = $arrGroupCoreSubMenu;
			}
			$arrayResult["CORE_SUB_MENUE"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else if(($dataComing['title_menu']=="setting")){
			$arrayGroup = array();
			$fetchMenuMobile = $conmysql->prepare("SELECT id_submenu, menu_name, menu_status  FROM `coresubmenu` 
												WHERE id_coremenu ='5' AND id_menuparent !='0'  
												ORDER BY id_menuparent ASC ");
			$fetchMenuMobile->execute();
			while($rowCoreSubMenu = $fetchMenuMobile->fetch()){
				$arrGroupCoreSubMenu = array();
				$arrGroupCoreSubMenu["ID_MENU"] = $rowCoreSubMenu["id_submenu"];
				$arrGroupCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
				$arrGroupCoreSubMenu["MENU_STATUS"] = $rowCoreSubMenu["menu_status"];
				$arrayGroup[] = $arrGroupCoreSubMenu;
			}
			$arrayResult["CORE_SUB_MENUE"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else if(($dataComing['title_menu']=="all")){
			$arrayGroup = array();
			$fetchMenuMobile = $conmysql->prepare("SELECT coresubmenu.id_submenu, coresubmenu.menu_name, coresubmenu.menu_status, coremenu.coremenu_name
													FROM coresubmenu
													INNER JOIN coremenu
													ON coresubmenu.id_coremenu = coremenu.id_coremenu
													WHERE id_menuparent != 0
													ORDER BY id_menuparent ASC");
			$fetchMenuMobile->execute();
			while($rowCoreSubMenu = $fetchMenuMobile->fetch()){
				$arrGroupCoreSubMenu = array();
				$arrGroupCoreSubMenu["ID_MENU"] = $rowCoreSubMenu["id_submenu"];
				$arrGroupCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
				$arrGroupCoreSubMenu["MENU_STATUS"] = $rowCoreSubMenu["menu_status"];
				
				$arrGroupCoreSubMenu["COREMENU_NAME"] = $rowCoreSubMenu["coremenu_name"];
				$arrayGroup[] = $arrGroupCoreSubMenu;
			}
			$arrayResult["CORE_SUB_MENUE"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ไม่ได้ระบุชื่อเมนูหลัก";
			http_response_code(403);
			echo json_encode($arrayResult);
			exit();
		}
		
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