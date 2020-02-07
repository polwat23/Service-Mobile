<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','managenew')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT id_news,news_title,news_detail,path_img_header,create_date,update_date,link_news_more,img_gallery_1,img_gallery_2,img_gallery_3,img_gallery_4,img_gallery_5 
										FROM gcnews
										WHERE is_use ='1'");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch()){
			$arrGroupCoreUser = array();
			$arrGroupCoreUser["ID_NEW"] = $rowCoreSubMenu["id_news"];
			$arrGroupCoreUser["NEWS_TITLE"] = $rowCoreSubMenu["news_title"];
			$arrGroupCoreUser["NEWS_DETAIL"] = $rowCoreSubMenu["news_detail"];
			$arrGroupCoreUser["PATH_IMG_HEADER"] = $rowCoreSubMenu["path_img_header"];
			$arrGroupCoreUser["LINK_News_MORE"] = $rowCoreSubMenu["link_news_more"];
			$arrGroupCoreUser["CREATE_DATE"] = $lib->convertdate($rowCoreSubMenu["create_date"],'d m Y',true); 
			$arrGroupCoreUser["UPDATE_DATE"] = $lib->convertdate($rowCoreSubMenu["update_date"],'d m Y',true);  
			$arrGroupCoreUser["PATH_IMG_1"] = $rowCoreSubMenu["img_gallery_1"];
			$arrGroupCoreUser["PATH_IMG_2"] = $rowCoreSubMenu["img_gallery_2"];
			$arrGroupCoreUser["PATH_IMG_3"] = $rowCoreSubMenu["img_gallery_3"];
			$arrGroupCoreUser["PATH_IMG_4"] = $rowCoreSubMenu["img_gallery_4"];
			$arrGroupCoreUser["PATH_IMG_5"] = $rowCoreSubMenu["img_gallery_5"];
			
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