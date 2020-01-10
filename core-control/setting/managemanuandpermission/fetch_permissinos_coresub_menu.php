<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'setting','permissionmenu')){

			if(($dataComing['title_menu']=="demo")){
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