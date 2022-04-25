<?php
require_once('../autoload.php');


	
	$arrayGroup = array();
	$fetchoftenlinkGroup = $conmysql->prepare("
				SELECT
					often.webcoopoftenlink_id,
						menu.type,
						menu.menu_name,
						menu.page_name,
						menu.id_menuparent,
						often.id_menu,
						often.create_by,
						often.update_by,
						often.create_date,
						often.update_date
				FROM
						webcoopmenuoftenlink often
				INNER JOIN webcoopmenu menu 
				ON often.id_menu = menu.id_menu
				WHERE is_use = '1'
				ORDER BY often.menu_order
				");
	$fetchoftenlinkGroup->execute();
	while($rowoftenlinkGroup = $fetchoftenlinkGroup->fetch(PDO::FETCH_ASSOC)){
		
		$fetchpageName = $conmysql->prepare("
				SELECT
					page_name
				FROM
					webcoopmenu
				WHERE id_menu = :id_menu
				");
		$fetchpageName->execute([':id_menu' =>  $rowoftenlinkGroup["id_menuparent"]]);
		$PageName = $fetchpageName->fetch(PDO::FETCH_ASSOC);
	
		$arrGroupStatement["WEBCOOPOFTENLINK_ID"] = $rowoftenlinkGroup["webcoopoftenlink_id"];
        $arrGroupStatement["ID_MENU"] = $rowoftenlinkGroup["id_menu"];
        $arrGroupStatement["TYPE"] = $rowoftenlinkGroup["type"];
        $arrGroupStatement["PAGE_NAME"] = $rowoftenlinkGroup["page_name"];
        $arrGroupStatement["MENUPARENT_ID"] = $rowoftenlinkGroup["id_menuparent"];
        $arrGroupStatement["MENUPARENT_NAME"] = $PageName["page_name"];
        $arrGroupStatement["MENU_NAME"] = $rowoftenlinkGroup["menu_name"];
        $arrGroupStatement["IS_USE"] = $rowoftenlinkGroup["is_use"];
		$arrGroupStatement["UPDATE_BY"] = $rowoftenlinkGroup["update_by"];
		$arrGroupStatement["CREATE_BY"] = $rowoftenlinkGroup["create_by"];
		$arrGroupStatement["CREATE_DATE"] = $lib->convertdate($rowoftenlinkGroup["create_date"],'d m Y',true); 
		$arrGroupStatement["UPDATE_DATE"] = $lib->convertdate($rowoftenlinkGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrGroupStatement;
	}
	
	$arrayResult["OFTENLINK_GROUP_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

?>