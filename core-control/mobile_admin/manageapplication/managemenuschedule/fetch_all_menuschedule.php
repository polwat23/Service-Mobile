<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenuschedule')){
		$arrayGroup = array();
		$fetchMenuSchedule = $conmysql->prepare("SELECT ms.id_menuschedule, ms.id_menu, ms.schedule_type, 
															ms.schedule_start, ms.start_menu_status, ms.start_menu_channel, ms.schedule_end, ms.end_menu_status, ms.end_menu_channel, 
															ms.create_by, ms.create_date, ms.update_by, ms.update_date, ms.is_use,ms.time_end,
                                                            mn.menu_name, mn.menu_status as current_status, mn.menu_channel as current_channel
                                                            FROM gcmenuschedule ms 
                                                            JOIN gcmenu mn ON mn.id_menu = ms.id_menu
                                                            WHERE ms.is_use = '1'
															ORDER BY ms.is_use DESC, ms.update_date DESC, ms.schedule_start DESC;");
		$fetchMenuSchedule->execute();
		while($rowMenuMobile = $fetchMenuSchedule->fetch(PDO::FETCH_ASSOC)){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_MENUSCHEDULE"] = $rowMenuMobile["id_menuschedule"];
			$arrGroupMenu["ID_MENU"] = $rowMenuMobile["id_menu"];
			$arrGroupMenu["SCHEDULE_TYPE"] = $rowMenuMobile["schedule_type"];
			$arrGroupMenu["SCHEDULE_START"] = $rowMenuMobile["schedule_start"];
			$arrGroupMenu["START_MENU_STATUS"] = $rowMenuMobile["start_menu_status"];
			$arrGroupMenu["START_MENU_CHANNEL"] = $rowMenuMobile["start_menu_channel"];
			$arrGroupMenu["SCHEDULE_END"] = $rowMenuMobile["schedule_end"];
			$arrGroupMenu["END_MENU_STATUS"] = $rowMenuMobile["end_menu_status"];
			$arrGroupMenu["END_MENU_CHANNEL"] = $rowMenuMobile["end_menu_channel"];
			$arrGroupMenu["CREATE_BY"] = $rowMenuMobile["create_by"];
			$arrGroupMenu["CREATE_DATE"] = $rowMenuMobile["create_date"]; 
			$arrGroupMenu["UPDATE_BY"] = $rowMenuMobile["update_by"];
			$arrGroupMenu["UPDATE_DATE"] = $rowMenuMobile["update_date"]; 
			$arrGroupMenu["IS_USE"] = $rowMenuMobile["is_use"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenu["CURRENT_STATUS"] = $rowMenuMobile["current_status"];
			$arrGroupMenu["CURRENT_CHANNEL"] = $rowMenuMobile["current_channel"];
			$arrGroupMenu["TIME_END"] = $rowMenuMobile["time_end"];
			$arrayGroup[] = $arrGroupMenu;
		}
		$arrayResult["SCHEDULE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>