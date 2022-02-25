<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuploadfiles')){
		$arrayGroup = array();
		$arrayExecute = array();
		$fetchNews = $conmysql->prepare("SELECT id_uploadfilecoop, file_name, path_file,
										type_upload, is_use, update_by, update_date, create_by, create_date 
										FROM gcuploadfilecoop
										WHERE is_use = '1'
										ORDER BY update_date desc");
		$fetchNews->execute($arrayExecute);
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrGroupNews = array();
			$arrGroupNews["ID_UPLOADFILECOOP"] = $rowNews["id_uploadfilecoop"];
			$arrGroupNews["FILE_NAME"] = $rowNews["file_name"];
			$arrGroupNews["PATH_FILE"] = $rowNews["path_file"];
			$arrGroupNews["TYPE_UPLOAD"] = $rowNews["type_upload"];
			$arrGroupNews["UPDATE_BY"] = $rowNews["update_by"];
			$arrGroupNews["CREATE_BY"] = $rowNews["create_by"];
			$arrGroupNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'d m Y',true); 
			$arrGroupNews["CREATE_DATE"] = $lib->convertdate($rowNews["create_date"],'d m Y',true); 
			
			$arrayGroup[] = $arrGroupNews;
		}
		$arrayResult["UPLOADFILE"] = $arrayGroup;
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