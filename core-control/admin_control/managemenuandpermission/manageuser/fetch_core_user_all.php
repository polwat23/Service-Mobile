<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT coreuser.username,coreuser.id_section_system, coresectionsystem.section_system, coresectionsystem.system_assign ,coreuser.user_status
											FROM coreuser
											INNER JOIN coresectionsystem
											ON coresectionsystem.id_section_system = coreuser.id_section_system");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch()){
			$arrGroupCoreUser = array();
			$arrGroupCoreUser["USERNAME"] = $rowCoreSubMenu["username"];
			$arrGroupCoreUser["ID_SECTION_SYSTEM"] = $rowCoreSubMenu["id_section_system"];
			$arrGroupCoreUser["SECTION_SYSTEM"] = $rowCoreSubMenu["section_system"];
			$arrGroupCoreUser["SYSTEM_ASSIGN"] = $rowCoreSubMenu["system_assign"];
			$arrGroupCoreUser["USER_STATUS"] = $rowCoreSubMenu["user_status"];
			$arrayGroup[] = $arrGroupCoreUser;
		}
		$arrayResult["CORE_USER"] = $arrayGroup;
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