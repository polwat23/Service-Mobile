<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic')){
		$fetchTopic = $conmysql->prepare("SELECT sm.menu_name,sm.id_submenu,smt.id_smstemplate,cpm.username,stm.smstemplate_name
											FROM coresubmenu sm LEFT JOIN smstopicmatchtemplate smt ON sm.id_submenu = smt.id_submenu
											LEFT JOIN smstemplate stm ON smt.id_smstemplate = stm.id_smstemplate
                                            LEFT JOIN corepermissionsubmenu smp ON sm.id_submenu = smp.id_submenu
											LEFT JOIN corepermissionmenu cpm ON smp.id_permission_menu = cpm.id_permission_menu
											WHERE sm.menu_status = '1' and sm.id_menuparent = 8 and smp.is_use = '1'");
		$fetchTopic->execute();
		$arrAllTopic = array();
		while($rowTopic = $fetchTopic->fetch()){
			$arrayTopic = array();
			$arrayTopic["TOPIC_NAME"] = $rowTopic["menu_name"];
			$arrayTopic["ID_SUBMENU"] = $rowTopic["id_submenu"];
			$arrayTopic["SMSTEMPLATE_NAME"] = $rowTopic["smstemplate_name"];
			$arrayTopic["ID_SMSTEMPLATE"] = $rowTopic["id_smstemplate"];
			if(array_search($rowTopic["menu_name"],array_column($arrAllTopic,"TOPIC_NAME")) === FALSE){
				($arrayTopic["USER_CONTROL"])[] = $rowTopic["username"];
				$arrAllTopic[] = $arrayTopic;
			}else{
				($arrAllTopic[array_search($rowTopic["menu_name"],array_column($arrAllTopic,"TOPIC_NAME"))]["USER_CONTROL"])[] = $rowTopic["username"];
			}
		}
		$arrayResult['TOPIC'] = $arrAllTopic;
		$arrayResult['RESULT'] = TRUE;
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