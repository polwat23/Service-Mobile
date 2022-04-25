<?php
require_once('../autoload.php');

	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	
	$fetchImgBanner = $conmysql->prepare("SELECT
												banner_id,
												news_id,
												img_path,
												img_url,
												type,
												update_date,
												type_link,
												url
											FROM
												webcoopbanner
											WHERE is_use ='1'
											ORDER BY update_date											
												");
	$fetchImgBanner->execute();
	$arrayGroupFile=[];		
	while($rowFile = $fetchImgBanner->fetch(PDO::FETCH_ASSOC)){
			$arrNewsFile["ID"] = $rowFile["banner_id"];
			$arrNewsFile["NEWS_ID"] = $rowFile["news_id"];
			$arrNewsFile["TYPE"] = $rowFile["type"];
			$arrNewsFile["TYPE_LINK"] = $rowFile["type_link"];
			$arrNewsFile["URL_LINK"] = $rowFile["url"];
			$arrNewsFile["url"]=$rowFile["img_url"];
			$arrayGroupFile[]=$arrNewsFile;
	}
		
	
	$arrayResult["BANNER_DATA"] = $arrayGroupFile;
	$arrayResult["IMG"] = $arrayGroupFile;
	
	
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
?>