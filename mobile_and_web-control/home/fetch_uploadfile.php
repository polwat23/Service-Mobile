<?php
require_once('../autoload.php');

	$arrayGroup = array();
	$arrayExecute = array();
	$fetchNews = $conmysql->prepare("SELECT id_uploadfile, file_name, file_detail, path_file,
									type_upload, is_use, update_by, update_date, create_by, create_date 
									FROM gcuploadfile
									WHERE is_use = '1'
									ORDER BY update_date desc");
	$fetchNews->execute($arrayExecute);
	while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
		$arrGroupNews = array();
		$arrGroupNews["ID_UPLOADFILE"] = $rowNews["id_uploadfile"];
		$arrGroupNews["FILE_NAME"] = $rowNews["file_name"];
		$arrGroupNews["FILE_DETAIL"] = $rowNews["file_detail"];
		$arrGroupNews["PATH_FILE"] = $rowNews["path_file"];
		$arrGroupNews["TYPE_UPLOAD"] = $rowNews["type_upload"];
		$arrGroupNews["UPDATE_BY"] = $rowNews["update_by"];
		$arrGroupNews["CREATE_BY"] = $rowNews["create_by"];
		$arrGroupNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'d m Y',true); 
		$arrGroupNews["CREATE_DATE"] = $lib->convertdate($rowNews["create_date"],'d m Y',true); 
		
		$arrayGroup[] = $arrGroupNews;
	}
	$arrayResult["UPLOADEDFILE"] = $arrayGroup;
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
?>