<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'document','viewdocuments')){
			if(isset($dataComing["docgrp_no"]) && $dataComing["docgrp_no"] !== ""){
				$arrayDocument = array();
				$arrayFile = array();
				//group
				$mainSelectDoc = "SELECT docgrp_no, docgrp_name, docgrp_ref, 
														create_date, update_date, create_by 
														FROM docgroupcontrol 
														WHERE docgrp_ref = :docgrp_no
														AND is_use = '1' ";
				$orderSelectDoc = "ORDER BY ".($dataComing["sorting_value"] == "CREATE_DATE" ? "create_date" : "docgrp_name")." ".($dataComing["sorting_order"] == "asc" ? "asc" : "desc");
				$selectDoccuments = $mainSelectDoc.$orderSelectDoc;
				$fetchDocumentSystems = $conmysql->prepare($selectDoccuments);
				$fetchDocumentSystems->execute([
					':docgrp_no' => $dataComing["docgrp_no"]
				]);
				while($dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC)){
					$systemsArray = array();
					$systemsArray["DOCGRP_NO"] = $dataSystem["docgrp_no"];
					$systemsArray["DOCGRP_NAME"] = $dataSystem["docgrp_name"];
					$systemsArray["DOCGRP_REF"] = $dataSystem["docgrp_ref"];
					$systemsArray["CREATE_BY"] = $dataSystem["create_by"];
					$systemsArray["TYPE"] = "FOLDER";
					$systemsArray["CREATE_DATE"] = $lib->convertdate($dataSystem["create_date"],'d m Y',true); 
					$systemsArray["UPDATE_DATE"] = $lib->convertdate($dataSystem["update_date"],'d m Y',true); 
					$arrayDocument[] = $systemsArray;
				}
				
				//file
				$mainSelectFile = "SELECT doc_no, docgrp_no, doc_filename, doc_type, source_ref, 
														doc_address, member_no, create_date, update_date, doc_status 
														FROM `doclistmaster` 
														WHERE doc_status = '1' AND docgrp_no = :docgrp_no ";
				$orderSelectFile = "ORDER BY ".($dataComing["sorting_value"] == "CREATE_DATE" ? "create_date" : "doc_filename")." ".($dataComing["sorting_order"] == "asc" ? "asc" : "desc");
				$selectFiles = $mainSelectFile.$orderSelectFile;
				$fetchDocument = $conmysql->prepare($selectFiles);
				$fetchDocument->execute([
					':docgrp_no' => $dataComing["docgrp_no"]
				]);
				
				while($dataDoc = $fetchDocument->fetch(PDO::FETCH_ASSOC)){
					$systemsArray = array();
					$systemsArray["DOC_NO"] = $dataDoc["doc_no"];
					$systemsArray["DOCGRP_NO"] = $dataDoc["docgrp_no"];
					$systemsArray["DOC_FILENAME"] = $dataDoc["doc_filename"];
					$systemsArray["DOC_TYPE"] = $dataDoc["doc_type"];
					$systemsArray["SOURCE_REF"] = $dataDoc["source_ref"];
					$systemsArray["DOC_ADDRESS"] = $dataDoc["doc_address"];
					$systemsArray["MEMBER_NO"] = $dataDoc["member_no"];
					$systemsArray["TYPE"] = "FILE";
					$systemsArray["CREATE_DATE"] = $lib->convertdate($dataDoc["create_date"],'d m Y',true); 
					$systemsArray["UPDATE_DATE"] = $lib->convertdate($dataDoc["update_date"],'d m Y',true); 
					$arrayFile[] = $systemsArray;
				}
				
				$arrayResult['DOCUMENTS'] = $arrayDocument;
				$arrayResult['FILES'] = $arrayFile;
				$arrayResult['DOCGRP_REF'] = $dataComing["docgrp_no"];
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayDocument = array();
				$mainSelectDoc = "SELECT docgrp_no, docgrp_name, docgrp_ref, 
														create_date, update_date, create_by 
														FROM docgroupcontrol 
														WHERE docgrp_ref IS NULL
														AND is_use = '1' ";
				$orderSelectDoc = "ORDER BY ".($dataComing["sorting_value"] == "CREATE_DATE" ? "create_date" : "docgrp_name")." ".($dataComing["sorting_order"] == "asc" ? "asc" : "desc");
				$selectDoccuments = $mainSelectDoc.$orderSelectDoc;
				$fetchDocumentSystems = $conmysql->prepare($selectDoccuments);
				$fetchDocumentSystems->execute();
				while($dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC)){
					$systemsArray = array();
					$systemsArray["DOCGRP_NO"] = $dataSystem["docgrp_no"];
					$systemsArray["DOCGRP_NAME"] = $dataSystem["docgrp_name"];
					$systemsArray["DOCGRP_REF"] = $dataSystem["docgrp_ref"];
					$systemsArray["CREATE_BY"] = $dataSystem["create_by"];
					$systemsArray["TYPE"] = "FOLDER";
					$systemsArray["CREATE_DATE"] = $lib->convertdate($dataSystem["create_date"],'d m Y',true); 
					$systemsArray["UPDATE_DATE"] = $lib->convertdate($dataSystem["update_date"],'d m Y',true); 
					$arrayDocument[] = $systemsArray;
				}
				$arrayResult['DOCUMENTS'] = $arrayDocument;
				$arrayResult['DOCGRP_REF'] = null;
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

