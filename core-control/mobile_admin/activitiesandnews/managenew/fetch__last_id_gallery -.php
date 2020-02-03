<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','managenew')){
		$arrayGroup = array();
		$maxIdGallery = $conmysql->prepare("SELECT  MAX(id_gallery)  AS max_id
										    FROM gcgallery");
		$maxIdGallery->execute();
			while($rowGallery = $maxIdGallery->fetch()){
			 $stmt = $conmysql->prepare("SELECT MAX(id_gallery)  AS max_id  FROM gcgallery ");
			 $stmt->execute();
			$invNum = $stmt -> fetch(PDO::FETCH_ASSOC);
			$max_id = $invNum['max_id'];
			$arrGallery = array();
			$arrGallery["ID_GALLERY"] = $rowGallery["max_id"];	
			$arrGallery["MAX_ID"] = $max_id ;					
			$arrayGroup[] = $arrGallery;
		}
		$arrayResult["GALLERY_DATA"] = $arrayGroup;
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