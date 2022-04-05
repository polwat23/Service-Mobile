<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','uploaddocuments')){
		$fetchDocFile = $conmysql->prepare("SELECT doc_no, doc_filename, doc_address, create_date FROM gcdocuploadfile where doc_status = '1'");
		$fetchDocFile->execute();
		
		$arrayDocFile= array();
		
		while($rowDocFile = $fetchDocFile->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["DOC_NO"] = $rowDocFile["doc_no"];
			$arrayGroup["DOC_FILENAME"] = $rowDocFile["doc_filename"];
			$arrayGroup["URL_PATH"] = $rowDocFile["doc_address"];
			$arrayGroup["UPLOAD_DATE"] = $lib->convertdate($rowDocFile["create_date"],"D M Y");
			$arrayDocFile[] = $arrayGroup;
		}
		
		$arrayResult["DOC_FILE"] = $arrayDocFile;
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