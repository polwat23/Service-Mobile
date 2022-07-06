<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$fetchUserControl = $conoracle->prepare("SELECT am.DESCRIPTION,am.USER_NAME,hr.DEPTGRP_CODE FROM amsecusers am 
											LEFT JOIN hremployee hr ON am.user_id = hr.emp_no");
	$fetchUserControl->execute();
	$arrayGroupAll = array();
	while($rowUserControl = $fetchUserControl->fetch()){
		$arrayGroupUC = array();
		$arrayGroupUC["SYSTEM_ASSIGN"] = $rowUserControl["DESCRIPTION"];
		$arrayGroupUC["SECTION_SYSTEM"] = $rowUserControl["DEPTGRP_CODE"];
		$arrayGroupUC["ID_SECTION_SYSTEM"] = $rowUserControl["DEPTGRP_CODE"];
		if(array_search($rowUserControl["DEPTGRP_CODE"],array_column($arrayGroupAll,"SECTION_SYSTEM")) === FALSE){
			($arrayGroupUC["USER_CONTROL"])[] = $rowUserControl["USER_NAME"];
			$arrayGroupAll[] = $arrayGroupUC;
		}else{
			($arrayGroupAll[array_search($rowUserControl["section_system"],array_column($arrayGroupAll,"SECTION_SYSTEM"))]["USER_CONTROL"])[] = $rowUserControl["USER_NAME"];
		}
	}
	$arrayResult['SYSTEM_CONTROL'] = $arrayGroupAll;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
