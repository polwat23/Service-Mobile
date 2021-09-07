<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'document','viewdocuments')){
				$arrayFile = array();
				$arrayExecute = array();
				if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
					$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
				}
				
				if(isset($dataComing["doc_no"]) && $dataComing["doc_no"] != ''){
					$arrayExecute[':doc_no'] = '%'.$dataComing["doc_no"].'%';
				}
				
				//file
				$mainSelectFile = "SELECT doc_no, docgrp_no, doc_filename, doc_type, source_ref, 
								doc_address, member_no, create_date, update_date, doc_status 
								FROM `doclistmaster` 
								WHERE doc_status = '1'".
								(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and member_no = :member_no " : null).
								(isset($dataComing["doc_no"]) && $dataComing["doc_no"] != '' ? " and doc_no LIKE :doc_no " : null);
								
				$orderSelectFile = "ORDER BY ".($dataComing["sorting_value"] == "CREATE_DATE" ? "create_date" : "doc_filename")." ".($dataComing["sorting_order"] == "asc" ? "asc" : "desc");
				$selectFiles = $mainSelectFile.$orderSelectFile;
				$fetchDocument = $conmssql->prepare($selectFiles);
				$fetchDocument->execute($arrayExecute);
				
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
				
				$arrayResult['DOCUMENTS'] = array();
				$arrayResult['FILES'] = $arrayFile;
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

