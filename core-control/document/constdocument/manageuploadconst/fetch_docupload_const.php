<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','manageuploadconst')){
		$arrayGroup = array();
		$fetchDocUploadConst = $conmssql->prepare("SELECT id_upload, upload_system,upload_system_desc, menu_component 
													FROM docuploadconstant WHERE is_use = '1'");
		$fetchDocUploadConst->execute();
		while($rowConst = $fetchDocUploadConst->fetch(PDO::FETCH_ASSOC)){
			$systemsArray = array();
			$systemsArray["ID_UPLOAD"] = $rowConst["id_upload"];	
			$systemsArray["UPLOAD_SYSTEM"] = $rowConst["upload_system"];	
			$systemsArray["UPLOAD_SYSTEM_DESC"] = $rowConst["upload_system_desc"];
			$systemsArray["MENU_COMPONENT"] = $rowConst["menu_component"];
			$arrayGroup[] = $systemsArray;
		}
		$arrayResult['DOCUPLOAD_CONST'] = $arrayGroup;
		$arrayResult['RESULT'] = TRUE;
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

