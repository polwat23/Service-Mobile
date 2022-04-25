<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$fetchlinkGroup = $conmysql->prepare("SELECT
											of.webcoopoftenlink_id,
											of.id_menu,
											of.menu_order,
											mn.menu_name,
											mn.type,
											of.create_by,
											of.update_by,
											of.create_date,
											of.update_date,
											of.is_use
										FROM
											webcoopmenuoftenlink of
										LEFT JOIN webcoopmenu mn ON
											of.id_menu = mn.id_menu
										WHERE
											of.is_use <> '-9' AND of.is_use <> '-9'
										ORDER BY
											of.menu_order");
	$fetchlinkGroup->execute();
	while($rowoftenlinkGroup = $fetchlinkGroup->fetch(PDO::FETCH_ASSOC)){
		$arrLinkOften["WEBCOOPOFTENLINK_ID"] = $rowoftenlinkGroup["webcoopoftenlink_id"];
		$arrLinkOften["ID_MENU"] = $rowoftenlinkGroup["id_menu"];
		$arrLinkOften["TYPE"] = $rowoftenlinkGroup["type"];
		$arrLinkOften["MENU_ORDER"] = $rowoftenlinkGroup["menu_order"];
		$arrLinkOften["MENU_NAME"] = $rowoftenlinkGroup["menu_name"];
		$arrLinkOften["UPDATE_BY"] = $rowoftenlinkGroup["update_by"];
		$arrLinkOften["CREATE_BY"] = $rowoftenlinkGroup["create_by"];
		$arrLinkOften["IS_USE"] = $rowoftenlinkGroup["is_use"];
		$arrLinkOften["CREATE_DATE"] = $lib->convertdate($rowoftenlinkGroup["create_date"],'d m Y',true); 
		$arrLinkOften["UPDATE_DATE"] = $lib->convertdate($rowoftenlinkGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrLinkOften;
	}
	$arrayResult["OFTENLINK_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>