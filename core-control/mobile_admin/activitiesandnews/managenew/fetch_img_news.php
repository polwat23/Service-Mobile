<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_gallery'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','managenew')){
		$arrayGroup = array();
		$fetchGallery = $conmysql->prepare("SELECT  id_news, img_gallery_1, img_gallery_2, img_gallery_3, img_gallery_4, img_gallery_5
										  FROM gcnews
										  WHERE id_news='$dataComing[id_gallery]'");
		$fetchGallery->execute();
		while($rowGallery = $fetchGallery->fetch()){
			$arrGallery = array();
			$arrGallery["ID_GALLERY"] = $rowGallery["id_news"];
			$arrGallery["PATH_IMG_1"] = $rowGallery["img_gallery_1"];
			$arrGallery["PATH_IMG_2"] = $rowGallery["img_gallery_2"];
			$arrGallery["PATH_IMG_3"] = $rowGallery["img_gallery_3"];
			$arrGallery["PATH_IMG_4"] = $rowGallery["img_gallery_4"];
			$arrGallery["PATH_IMG_5"] = $rowGallery["img_gallery_5"];
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