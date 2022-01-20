<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantreqdocumentformtype')){
		$arrayGroup = array();
		$fetchDocumentType = $conmysql->prepare("SELECT reqdocformtype_id, documenttype_desc, documentform_url, update_date, is_use
																		FROM gcreqdocformtype WHERE is_use in('1','0')");
		$fetchDocumentType->execute();
		while($rowConst = $fetchDocumentType->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["REQDOCFORMTYPE_ID"] = $rowConst["reqdocformtype_id"];
			$arrConst["DOCUMENTTYPE_DESC"] = $rowConst["documenttype_desc"];
			$arrConst["DOCUMENTFORM_URL"] = $rowConst["documentform_url"];
			$arrConst["ACTIVE"] = $rowConst["is_use"] == "1";
			$arrConst["UPDATE_DATE"] = $lib->convertdate($rowConst["update_date"],'d m Y',true);
			$arrayGroup[] = $arrConst;
		}
		$arrayResult["DOCUMENT_TYPE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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