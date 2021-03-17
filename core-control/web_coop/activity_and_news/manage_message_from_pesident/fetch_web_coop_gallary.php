<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managenewswebcoop')){
		$arrayGroup = array();
		$fetchNews = $conmysql->prepare("SELECT
											id_gallery,
											img_gallery_url
										FROM
											webcoopgallary
										WHERE img_gallery_url IS NOT NULL
										ORDER BY
											id_gallery
										DESC");
		$fetchNews->execute();
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrGroupNews = array();
			$arrGroupNews["ID_GALLARY"] = $rowNews["id_gallery"];
			$arrGroupNews["PATH_FILE"] = $rowNews["img_gallery_url"];
			$arrayGroup[] = $arrGroupNews;
		}
		$arrayResult["GALLARY_DATA"] = $arrayGroup;
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