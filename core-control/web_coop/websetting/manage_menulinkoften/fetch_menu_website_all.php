<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	
	
	if(isset($dataComing["currenMenuSelect"]) && $dataComing["currenMenuSelect"] != null){
		$fetchOftenMemu = $conmysql->prepare("SELECT id_menu FROM  webcoopmenuoftenlink WHERE is_use = '1' AND id_menu NOT IN(:id_menu)");
		$fetchOftenMemu->execute([':id_menu' => $dataComing["currenMenuSelect"]]);
	}else{
		$fetchOftenMemu = $conmysql->prepare("SELECT id_menu FROM webcoopmenuoftenlink WHERE is_use = '1' ");
		$fetchOftenMemu->execute();
	}

	$dataOftenMenu = array();
	while($rowoftenlinkGroup = $fetchOftenMemu->fetch(PDO::FETCH_ASSOC)){
		$arrOftenLink = $rowoftenlinkGroup["id_menu"];
		$dataOftenMenu[] = $arrOftenLink;
	}
	
	$fetchMemuWebsite = $conmysql->prepare("SELECT
												id_menu,
												menu_name,
												menu_status,
												menu_order
											FROM
												webcoopmenu
											WHERE 
											TYPE <> '1' AND menu_status = '1'
".(count($dataOftenMenu) > 0 ? ("AND id_menu NOT IN('".implode("','",$dataOftenMenu)."')") : null)."");
	$fetchMemuWebsite->execute();
	while($rowoftenlinkGroup = $fetchMemuWebsite->fetch(PDO::FETCH_ASSOC)){
		$arrMenu["ID_MENU"] = $rowoftenlinkGroup["id_menu"];
		$arrMenu["MENU_NAME"] = $rowoftenlinkGroup["menu_name"];
		$arrMenu["MENU_STATUS"] = $rowoftenlinkGroup["menu_status"];
		$arrMenu["MENU_ORDER"] = $rowoftenlinkGroup["menu_order"];
		$arrayGroup[] = $arrMenu;
	}
	$arrayResult["MENU_DATA"] = $arrayGroup;
	$arrayResult["ID_MENU"] = $fetchMemuWebsite;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>