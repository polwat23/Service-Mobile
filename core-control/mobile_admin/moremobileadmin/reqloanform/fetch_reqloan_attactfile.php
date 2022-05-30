<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','reqloan_doc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrGrp = array();
		$getAttachFile = $conmysql->prepare("SELECT rf.reqattach_id,rf.file_id,rf.reqdoc_no,rf.file_path,f.file_name FROM gcreqloanattachment rf 
									LEFT JOIN gcreqfileattachment f ON f.file_id = rf.file_id
									WHERE rf.reqdoc_no = :reqloan_doc");
		$getAttachFile->execute([':reqloan_doc' => $dataComing["reqloan_doc"]]);
		while($rowAttachFile = $getAttachFile->fetch(PDO::FETCH_ASSOC)){
			$arrayReq = array();
			$arrayReq["FILE_PATH"] = $rowAttachFile["file_path"];
			$arrayReq["FILE_NAME"] = $rowAttachFile["file_name"];
			$arrayReq["FILE_ID"] = $rowAttachFile["file_id"];
			$arrGrp[] = $arrayReq;
		}
		
		$arrayResult['ATTACHFILE_LIST'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>