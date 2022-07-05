<?php
require_once('autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$fetchMenu = $conoracle->prepare("SELECT cm.id_coremenu,cm.coremenu_name,cm.coremenu_iconpath,cm.coremenu_status,
									cm.coremenu_desc,cm.coremenu_colorbanner,cm.root_path,cm.coremenu_colortext
									FROM coremenu cm 
									WHERE cm.coremenu_status NOT IN('0','-99') and cm.coremenu_parent = '0'
									ORDER BY cm.coremenu_order ASC");
	$fetchMenu->execute();
	while($rowMenu = $fetchMenu->fetch(PDO::FETCH_ASSOC)){
		
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
	$arrayResult["MENU"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	require_once('../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../include/exit_footer.php');
	
}
?>