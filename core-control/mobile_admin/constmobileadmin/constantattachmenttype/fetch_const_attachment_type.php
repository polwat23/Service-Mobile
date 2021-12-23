<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantattachmenttype')){
		$arrayGroup = array();
		$fetchConstChangeInfo = $conmysql->prepare("SELECT file_id, file_name, file_desc, update_date, update_user FROM gcreqfileattachment WHERE is_use = '1'");
		$fetchConstChangeInfo->execute();
		while($rowConst = $fetchConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["FILE_ID"] = $rowConst["file_id"];
			$arrConst["FILE_NAME"] = $rowConst["file_name"];
			$arrConst["FILE_DESC"] = $rowConst["file_desc"];
			$arrConst["UPDATE_DATE"] = $rowConst["update_date"];
			$arrConst["UPDATE_USER"] = $rowConst["update_user"];
			$arrayGroup[] = $arrConst;
		}
		$arrayResult["CONST_FILE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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