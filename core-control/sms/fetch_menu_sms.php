<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && $lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'sms',$conmysql)){
		$arrayGroup = array();
		$fetchMenu = $conmysql->prepare("SELECT id_coremenu,coremenu_name,root_path
										FROM coremenu WHERE coremenu_status = '1' and coremenu_parent = '1'");
		$fetchMenu->execute();
		while($rowMenu = $fetchMenu->fetch()){
			$arrGroupRootMenu = array();
			$arrGroupRootMenu["ROOT_MENU_NAME"] = $rowMenu["coremenu_name"];
			$arrGroupRootMenu["ROOT_PATH"] = $rowMenu["root_path"];
			$fetchMenuSMS = $conmysql->prepare("SELECT sms_menu_name,page_name
												FROM smsmenu WHERE sms_menu_status = '1' and id_coremenu = :id_coremenu");
			$fetchMenuSMS->execute([':id_coremenu' => $rowMenu["id_coremenu"]]);
			while($rowSmsMenu = $fetchMenuSMS->fetch()){
				$arrayGroupSMS = array();
				$arrayGroupSMS["SMS_MENU_NAME"] = $rowSmsMenu["sms_menu_name"];
				$arrayGroupSMS["SMS_PAGE_NAME"] = '/sms/'.$rowMenu["root_path"].'/'.$rowSmsMenu["page_name"];
				($arrGroupRootMenu["SMS_MENU"])[] = $arrayGroupSMS;
			}
			$arrayGroup[] = $arrGroupRootMenu;
		}
		$arrayResult["MENU_SMS"] = $arrayGroup;
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