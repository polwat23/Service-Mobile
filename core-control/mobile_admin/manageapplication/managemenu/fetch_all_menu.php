<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'mobileadmin',$conmysql)){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT id_menu, menu_name, menu_status FROM gcmenu 
											  WHERE menu_status <>'9' AND menu_parent NOT IN ('-1','-2','-8','-9')  
											  ORDER BY menu_order ASC ");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch()){
			//````````````````````````
			$arrGroupMenuMobile = array();
			$arrGroupMenuMobile["ID_MENU"] = $rowMenuMobile["id_menu"];
			$arrGroupMenuMobile["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenuMobile["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			/*$arrGroupMenuMobile["LOGIN_DATE"] = $lib->convertdate($rowMenuMobile["login_date"],'d m Y',true);
			$arrGroupMenuMobile["MENU_ICON_PATH"] = $rowMenuMobile["menu_icon_path"];
			$arrGroupMenuMobile["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			$arrGroupMenuMobile["MENU_COMPONENT"] = $rowMenuMobile["menu_component"];
			$arrGroupMenuMobile["MENU_PERMISSION"] = $rowMenuMobile["menu_permission"];
			$arrGroupMenuMobile["MENU_PARENT"] = $rowMenuMobile["menu_parent"];
			$arrGroupMenuMobile["MENU_VERSION"] = $rowMenuMobile["menu_version"];
			$arrGroupMenuMobile["MENU_ORDER"] = $rowMenuMobile["menu_order"];
			$arrGroupMenuMobile["MENU_CHANNEL"] = $rowMenuMobile["menu_channel"];
			$arrGroupMenuMobile["UPDATE_DATE"] = $rowMenuMobile["update_date"];
			$arrGroupMenuMobile["CREATE_DATE"] = $rowMenuMobile["create_date"];
			*/
			$arrayGroup[] = $arrGroupMenuMobile;
			
		}
		$arrayResult["MENU_MOBILE"] = $arrayGroup;
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