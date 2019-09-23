<?php
require_once('../../autoload.php');

if(isset($dataComing["unique_id"]) && isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["id_news"])){
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'News')){
		$fetchDetailNews = $conmysql->prepare("SELECT mg.name_gallery,mg.update_date,mg.path_img_1,mg.path_img_2,mg.path_img_3,
											mg.path_img_4,mg.path_img_5,mb.news_title,mb.news_detail
											FROM mdbnews mb LEFT JOIN mdbgallery mg ON mb.id_gallery = mg.id_gallery 
											WHERE mb.id_news = :id_news");
		$fetchDetailNews->execute([':id_news' => $dataComing["id_news"]]);
		$rowDetailNews = $fetchDetailNews->fetch();
		$arrayDetailNews = array();
		$arrayDetailNews["TITLE"] = $rowDetailNews["news_title"];
		$arrayDetailNews["DETAIL"] = $rowDetailNews["news_detail"];
		$arrayDetailNews["NAME_GALLERY"] = $rowDetailNews["name_gallery"];
		$arrayDetailNews["UPDATE_DATE"] = $lib->convertdate($rowDetailNews["update_date"],'D m Y',true);
		$arrayDetailNews["IMG_1"] = $rowDetailNews["path_img_1"];
		$arrayDetailNews["IMG_2"] = $rowDetailNews["path_img_2"];
		$arrayDetailNews["IMG_3"] = $rowDetailNews["path_img_3"];
		$arrayDetailNews["IMG_4"] = $rowDetailNews["path_img_4"];
		$arrayDetailNews["IMG_5"] = $rowDetailNews["path_img_5"];
		$arrayResult['DETAIL_NEWS'] = $arrayDetailNews;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>