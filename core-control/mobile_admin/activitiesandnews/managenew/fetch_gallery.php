<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_gallery'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','managenew')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT  id_gallery,name_gallery, path_img_1, path_img_2, path_img_3, path_img_4, path_img_5, update_date
										  FROM gcgallery
										  WHERE id_gallery='$dataComing[id_gallery]'");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch()){
			$arrGallery = array();
			$arrGallery["ID_GALLERY"] = $rowCoreSubMenu["id_gallery"];
			$arrGallery["NAME_GALLERY"] = $rowCoreSubMenu["name_gallery"];
			$arrGallery["PATH_IMG_1"] = $rowCoreSubMenu["path_img_1"];
			$arrGallery["PATH_IMG_2"] = $rowCoreSubMenu["path_img_2"];
			$arrGallery["PATH_IMG_3"] = $rowCoreSubMenu["path_img_3"];
			$arrGallery["PATH_IMG_4"] = $rowCoreSubMenu["path_img_4"];
			$arrGallery["PATH_IMG_5"] = $rowCoreSubMenu["path_img_5"];
			$arrGallery["UPDATE_DATE"] = $rowCoreSubMenu["update_date"];
		
			
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