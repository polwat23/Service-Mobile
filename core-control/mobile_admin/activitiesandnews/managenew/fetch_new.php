<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','managenew')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT gcnews.id_news, gcnews.news_title, gcnews.news_detail, gcnews.path_img_header, gcnews.create_date, gcnews.update_date,gcnews.id_gallery ,gcgallery.path_img_1, gcgallery.path_img_2, gcgallery.path_img_3, gcgallery.path_img_4, gcgallery.path_img_5
										FROM gcnews
										INNER JOIN gcgallery
										ON gcgallery.id_gallery = gcnews.id_gallery");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch()){
			$arrGroupCoreUser = array();
			$arrGroupCoreUser["ID_NEW"] = $rowCoreSubMenu["id_news"];
			$arrGroupCoreUser["NEWS_TITLE"] = $rowCoreSubMenu["news_title"];
			$arrGroupCoreUser["NEWS_DETAIL"] = $rowCoreSubMenu["news_detail"];
			$arrGroupCoreUser["PATH_IMG_HEADER"] = $rowCoreSubMenu["path_img_header"];
			$arrGroupCoreUser["CREATE_DATE"] = $lib->convertdate($rowCoreSubMenu["create_date"],'d m Y',true); 
			$arrGroupCoreUser["UPDATE_DATE"] = $lib->convertdate($rowCoreSubMenu["update_date"],'d m Y',true);  
			$arrGroupCoreUser["ID_GALLERY"] = $rowCoreSubMenu["id_gallery"];
			$arrGroupCoreUser["PATH_IMG_1"] = $rowCoreSubMenu["path_img_1"];
			$arrGroupCoreUser["PATH_IMG_2"] = $rowCoreSubMenu["path_img_2"];
			$arrGroupCoreUser["PATH_IMG_3"] = $rowCoreSubMenu["path_img_3"];
			$arrGroupCoreUser["PATH_IMG_4"] = $rowCoreSubMenu["path_img_4"];
			$arrGroupCoreUser["PATH_IMG_5"] = $rowCoreSubMenu["path_img_5"];
			
			$arrayGroup[] = $arrGroupCoreUser;
		}
		$arrayResult["NEWS_DATA"] = $arrayGroup;
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