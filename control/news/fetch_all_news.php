<?php
require_once('../../autoload.php');

if(isset($dataComing["unique_id"]) && isset($dataComing["user_type"]) && isset($dataComing["menu_component"])){
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'News')){
		$arrayGroupNews = array();
		$fetchNews = $conmysql->prepare("SELECT news_title,news_detail,path_img_header,update_date,id_news
											FROM mdbnews LIMIT 5");
		$fetchNews->execute();
		while($rowNews = $fetchNews->fetch()){
			$arrayNews = array();
			$arrayNews["TITLE"] = $rowNews["news_title"];
			$arrayNews["DETAIL"] = $rowNews["news_detail"];
			$arrayNews["IMAGE_HEADER"] = $rowNews["path_img_header"];
			$arrayNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'D m Y',true);
			$arrayNews["ID_NEWS"] = $rowNews["id_news"];
			$arrayGroupNews[] = $arrayNews;
		}
		$arrayResult['NEWS'] = $arrayGroupNews;
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