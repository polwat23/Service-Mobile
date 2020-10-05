<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managebanner')){
		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		
			
		$fetchImgBanner = $conmysql->prepare("SELECT
													banner_id,
													news_id,
													img_path,
													img_url,
													type,
													update_date
												FROM
													webcoopbanner
												ORDER BY
													update_date");
		$fetchImgBanner->execute();
		$arrayGroupFile=[];		
		while($rowFile = $fetchImgBanner->fetch(PDO::FETCH_ASSOC)){
				$arrNewsFile["ID"] = $rowFile["banner_id"];
				$arrNewsFile["NEWS_ID"] = $rowFile["news_id"];
				$arrNewsFile["TYPE"] = $rowFile["type"];
				$arrNewsFile["original"]=$rowFile["img_url"];
				$arrNewsFile["thumbnail"]=$rowFile["img_url"];
				$arrNewsFile["IMG_PATH"]= $rowFile["img_path"];
			
				$arrayGroupFile[]=$arrNewsFile;
		}
			
		
		$arrayResult["ACTIVITY_DATA"] = $arrayGroupFile;
		$arrayResult["IMG"] = $arrayGroupFile;
		
		
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