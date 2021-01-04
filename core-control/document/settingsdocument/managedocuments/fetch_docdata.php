<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id', 'doc_system'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		if(false){
		}else{
			$arrayDocument = array();
			$fetchDocumentSystems = $conmysql->prepare("SELECT doc_system, system_name, system_desc, docgrp_no_prefix, create_date, update_date 
												FROM docsystem WHERE doc_system = :doc_system");
			$fetchDocumentSystems->execute([
				':doc_system' => $dataComing["doc_system"]
			]);
			if($fetchDocumentSystems->rowCount() > 0){
				$dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC);
				$systemsArray = array();
				$systemsArray["DOC_SYSTEM"] = $dataSystem["doc_system"];
				$systemsArray["SYSTEM_NAME"] = $dataSystem["system_name"];
				$systemsArray["SYSTEM_DESC"] = $dataSystem["system_desc"];
				$systemsArray["DOCGRP_NO_PREFIX"] = $dataSystem["docgrp_no_prefix"];
				$systemsArray["TYPE"] = "FOLDER";
				$systemsArray["CREATE_DATE"] = $lib->convertdate($dataSystem["create_date"],'d m Y',true); 
				$systemsArray["UPDATE_DATE"] = $lib->convertdate($dataSystem["update_date"],'d m Y',true); 
				$arrayDocument = $systemsArray;
			
				$arrayResult['DOC_DATA'] = $arrayDocument;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				http_response_code(403);
				require_once('../../../../include/exit_footer.php');
			}
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

