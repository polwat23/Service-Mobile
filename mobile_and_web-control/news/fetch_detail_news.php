<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['id_news'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'News')){
		$fetchDetailNews = $conmysql->prepare("SELECT ga.name_gallery,ga.update_date,ga.path_img_1,ga.path_img_2,ga.path_img_3,
											ga.path_img_4,ga.path_img_5,gn.news_title,gn.news_detail,gn.create_by
											FROM gcnews gn LEFT JOIN gcgallery ga ON gn.id_gallery = ga.id_gallery 
											WHERE gn.id_news = :id_news");
		$fetchDetailNews->execute([':id_news' => $dataComing["id_news"]]);
		$rowDetailNews = $fetchDetailNews->fetch();
		$arrayDetailNews = array();
		$arrayDetailNews["TITLE"] = $rowDetailNews["news_title"];
		$arrayDetailNews["DETAIL"] = $rowDetailNews["news_detail"];
		$arrayDetailNews["NAME_GALLERY"] = $rowDetailNews["name_gallery"];
		$arrayDetailNews["CREATE_BY"] = $rowDetailNews["create_by"];
		$arrayDetailNews["UPDATE_DATE"] = $lib->convertdate($rowDetailNews["update_date"],'D m Y',true);
		$path_img = array();
		for($i = 1; $i <=5; $i++){
			if(isset($rowDetailNews["path_img_".$i])){
				$path_img[] = $rowDetailNews["path_img_".$i];
			}
		}
		$arrayDetailNews["IMG"] = $path_img;
		$arrayResult['DETAIL_NEWS'] = $arrayDetailNews;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>