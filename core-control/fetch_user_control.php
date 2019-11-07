<?php
require_once('autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && $lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$fetchUserControl = $conmysql->prepare("SELECT cu.username,cs.section_system,cs.system_assign,cs.id_section_system
											FROM coreuser cu INNER JOIN coresectionsystem cs ON cu.id_section_system = cs.id_section_system
											WHERE cu.user_status = '1'");
	$fetchUserControl->execute();
	$arrayGroupAll = array();
	while($rowUserControl = $fetchUserControl->fetch()){
		$arrayGroupUC = array();
		$arrayGroupUC["SYSTEM_ASSIGN"] = $rowUserControl["system_assign"];
		$arrayGroupUC["SECTION_SYSTEM"] = $rowUserControl["section_system"];
		$arrayGroupUC["ID_SECTION_SYSTEM"] = $rowUserControl["id_section_system"];
		if(array_search($rowUserControl["section_system"],array_column($arrayGroupAll,"SECTION_SYSTEM")) === FALSE){
			($arrayGroupUC["USER_CONTROL"])[] = $rowUserControl["username"];
			$arrayGroupAll[] = $arrayGroupUC;
		}else{
			($arrayGroupAll[array_search($rowUserControl["section_system"],array_column($arrayGroupAll,"SECTION_SYSTEM"))]["USER_CONTROL"])[] = $rowUserControl["username"];
		}
	}
	$arrayResult['SYSTEM_CONTROL'] = $arrayGroupAll;
	$arrayResult['RESULT'] = TRUE;
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