<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','uploaddocuments')){
		$arrayDocFile= array();
		$fetchDocFile = $conmysql->prepare("SELECT doc_no, doc_filename, doc_address, member_no, create_date, doc_status ,open_status FROM documentreceive WHERE doc_status ='1' ORDER BY create_date DESC");
		$fetchDocFile->execute();	
		while($rowDocFile = $fetchDocFile->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["DOC_NO"] = $rowDocFile["doc_no"];
			$arrayGroup["MEMBER_NO"] = $rowDocFile["member_no"];
				$fetchMember = $conoracle->prepare("SELECT MP.PRENAME_DESC , MB.MEMB_NAME , MB.MEMB_SURNAME 
													FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE  = MP.PRENAME_CODE
													WHERE MB.MEMBER_NO = :member_no");
				$fetchMember->execute([':member_no' => $rowDocFile["member_no"]]);
				$rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC);
			$arrayGroup["MEMB_NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].'  '.$rowMember["MEMB_SURNAME"];
			$arrayGroup["DOC_ADDRESS"] = $rowDocFile["doc_address"];
			$arrayGroup["DOC_FILENAME"] = $rowDocFile["doc_filename"];
			$arrayGroup["DOC_ADDRESS"] = $rowDocFile["doc_address"];
			$arrayGroup["OPEN_STATUS"] = $rowDocFile["open_status"];
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