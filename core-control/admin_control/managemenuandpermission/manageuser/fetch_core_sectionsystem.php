<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT  id_section_system, section_system, system_assign
										 FROM  coresectionsystem
										 WHERE is_use ='1'");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreSectionSystem = array();
			$arrGroupCoreSectionSystem["ID_SECTION_SYSTEM"] = $rowCoreSubMenu["id_section_system"];
			$arrGroupCoreSectionSystem["SECTION_SYSTEM"] = $rowCoreSubMenu["section_system"];
			$arrGroupCoreSectionSystem["SYSTEM_ASSIGN"] = $rowCoreSubMenu["system_assign"];
			$arrayGroup[] = $arrGroupCoreSectionSystem;
		}
		$arrayResult["CORE_SECTION_SYSTEM"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>