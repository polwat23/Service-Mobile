<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		if(isset($dataComing["docgrp_no"]) && $dataComing["docgrp_no"] != ""){
			$fetchDocumentSystems = $conmssql->prepare("SELECT menu_component
												FROM docgroupcontrol
												WHERE is_use = '1' AND docgrp_no = :docgrp_no");
			$fetchDocumentSystems->execute([
				':docgrp_no' => $dataComing["docgrp_no"]
			]);
			if($fetchDocumentSystems->rowCount() > 0){
				$dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC);
				if(isset($dataSystem["menu_component"]) && $dataSystem["menu_component"] != ""){
					$arrayGroup = array();
					$fetchDocUploadConst = $conmssql->prepare("SELECT id_upload, upload_system,upload_system_desc, menu_component 
																FROM docuploadconstant WHERE is_use = '1' AND menu_component = :menu_component");
					$fetchDocUploadConst->execute([
						':menu_component' => $dataSystem["menu_component"]
					]);
					while($rowConst = $fetchDocUploadConst->fetch(PDO::FETCH_ASSOC)){
						$systemsArray = array();
						$systemsArray["KEY"] = $rowConst["upload_system"];	
						$systemsArray["LABEL"] = $rowConst["upload_system_desc"];	
						$arrayGroup[] = $systemsArray;
					}
					$arrayResult['DOCUPLOAD_CONST'] = $arrayGroup;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$arrayGroup = array();
					$fetchDocUploadConst = $conmssql->prepare("SELECT id_menu, menu_name,menu_component
													FROM gcmenu 
													WHERE menu_status <> '-9' AND menu_parent IN(0,18,19,-9,-8,-1)
													ORDER BY menu_order ASC ");
					$fetchDocUploadConst->execute();
					while($rowConst = $fetchDocUploadConst->fetch(PDO::FETCH_ASSOC)){
						$systemsArray = array();
						$systemsArray["KEY"] = $rowConst["menu_component"];		
						$systemsArray["LABEL"] = $rowConst["menu_name"];	
						$arrayGroup[] = $systemsArray;
					}
					$arrayResult['DOCUPLOAD_CONST'] = $arrayGroup;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['DOCUPLOAD_CONST'] = array();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayGroup = array();
			$fetchDocUploadConst = $conmssql->prepare("SELECT id_menu, menu_name,menu_component
													FROM gcmenu 
													WHERE menu_status <> '-9' AND menu_parent IN(0,18,19,-9,-8,-1)
													ORDER BY menu_order ASC");
			$fetchDocUploadConst->execute();
			while($rowConst = $fetchDocUploadConst->fetch(PDO::FETCH_ASSOC)){
				$systemsArray = array();
				$systemsArray["KEY"] = $rowConst["menu_component"];		
				$systemsArray["LABEL"] = $rowConst["menu_name"];	
				$arrayGroup[] = $systemsArray;
			}
			$arrayResult['DOCUPLOAD_CONST'] = $arrayGroup;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}
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

