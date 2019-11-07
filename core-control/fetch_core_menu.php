<?php
require_once('autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && $lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$fetchMenu = $conmysql->prepare("SELECT id_coremenu,coremenu_name,coremenu_iconpath,coremenu_status,
									coremenu_desc,coremenu_colorbanner,root_path,coremenu_colortext
									FROM coremenu WHERE coremenu_status NOT IN('0','-99') and coremenu_parent = '0' ORDER BY coremenu_order ASC");
	$fetchMenu->execute();
	while($rowMenu = $fetchMenu->fetch()){
		$arrMenu = array();
		$arrMenu["MENU_NAME"] = $rowMenu["coremenu_name"];
		$arrMenu["MENU_ICONPATH"] = $rowMenu["coremenu_iconpath"];
		$arrMenu["MENU_ID"] = $rowMenu["id_coremenu"];
		$arrMenu["MENU_STATUS"] = $rowMenu["coremenu_status"];
		$arrMenu["MENU_DESC"] = $rowMenu["coremenu_desc"];
		$arrMenu["MENU_COLOR_BANNER"] = $rowMenu["coremenu_colorbanner"];
		$arrMenu["MENU_TEXT_COLOR"] = $rowMenu["coremenu_colortext"];
		$arrMenu["ROOT_PATH"] = $rowMenu["root_path"];
		$arrayGroup[] = $arrMenu;
	}
	$arrayResult["MENU"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
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