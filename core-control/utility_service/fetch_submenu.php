<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','rootmenu'],$dataComing)){
	if($func->check_permission_core($payload,$dataComing["rootmenu"],null,$conmysql)){
		$arrayGroup = array();
		$fetchMenu = $conmysql->prepare("SELECT css.menu_name,css.page_name,css.id_submenu FROM coresubmenu css LEFT JOIN coremenu cm 
										ON css.id_coremenu = cm.id_coremenu and cm.coremenu_status = '1'
										WHERE css.id_menuparent = 0 and cm.root_path = :rootmenu and css.menu_status = '1' ORDER BY css.menu_order ASC");
		$fetchMenu->execute([':rootmenu' => $dataComing["rootmenu"]]);
		while($rowMenu = $fetchMenu->fetch()){
			$arrGroupRootMenu = array();
			$arrGroupRootMenu["ROOT_MENU_NAME"] = $rowMenu["menu_name"];
			$arrGroupRootMenu["ROOT_PATH"] = $rowMenu["page_name"];
			$fetchSubMenu = $conmysql->prepare("SELECT csm.menu_name,csm.page_name FROM coresubmenu csm LEFT JOIN corepermissionsubmenu cpsm 
												ON csm.id_submenu = cpsm.id_submenu and cpsm.is_use = '1'
												LEFT JOIN corepermissionmenu cpm ON cpsm.id_permission_menu = cpm.id_permission_menu and cpm.is_use = '1'
												WHERE csm.menu_status = '1' and csm.id_menuparent = :id_submenu and cpm.username = :username 
												ORDER BY csm.menu_order ASC");
			$fetchSubMenu->execute([
				':id_submenu' => $rowMenu["id_submenu"],
				':username' => $payload["username"]
			]);
			while($rowSubMenu = $fetchSubMenu->fetch()){
				if(isset($rowSubMenu["menu_name"])){
					$arrayGroupSubMenu = array();
					$arrayGroupSubMenu["SUB_MENU_NAME"] = $rowSubMenu["menu_name"];
					$arrayGroupSubMenu["SUB_PAGE_NAME"] = '/'.$dataComing["rootmenu"].'/'.$rowMenu["page_name"].'/'.$rowSubMenu["page_name"];
					($arrGroupRootMenu["SUB_MENU"])[] = $arrayGroupSubMenu;
				}
			}
			if(sizeof($arrGroupRootMenu["SUB_MENU"]) > 0){
				$arrayGroup[] = $arrGroupRootMenu;
			}
		}
		$arrayResult["SUB_MENU"] = $arrayGroup;
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