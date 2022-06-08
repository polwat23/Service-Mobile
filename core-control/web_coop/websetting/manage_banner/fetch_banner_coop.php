<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroupData = array();
	if($dataComing["status"] == "9"){
		$fetchImgBanner = $conmysql->prepare("
											SELECT
												banner_id,
												type_link,
												news_id,
												img_path,
												img_url,
												type,
												update_date,
												is_use,
												url
											FROM
												webcoopbanner
											WHERE is_use <> '-9'
											ORDER BY is_use DESC
											");
		$fetchImgBanner->execute();
	}else{
		$fetchImgBanner = $conmysql->prepare("
											SELECT
												banner_id,
												type_link,
												news_id,
												img_path,
												img_url,
												type,
												update_date,
												is_use,
												url
											FROM
												webcoopbanner
											WHERE is_use = :status
											ORDER BY create_date DESC");
		$fetchImgBanner->execute([
			':status' => $dataComing["status"]
		]);
	}
	$arrayGroupData=[];		
			
	while($rowFile = $fetchImgBanner->fetch(PDO::FETCH_ASSOC)){
			$arrBanner["ID"] = $rowFile["banner_id"];
			$arrBanner["NEWS_ID"] = $rowFile["news_id"];
			$arrBanner["TYPE"] = $rowFile["type"];
			$arrBanner["original"]=$rowFile["img_url"];
			$arrBanner["thumbnail"]=$rowFile["img_url"];
			$arrBanner["IMG_PATH"]= $rowFile["img_path"];
			$arrBanner["IS_USE"]= $rowFile["is_use"];
			$arrBanner["TYPE_LINK"]= $rowFile["type_link"];
			$arrBanner["URL"]= $rowFile["url"];
			$arrayGroupData[]=$arrBanner;
	}
	$arrayResult["BANNER_DATA"] = $arrayGroupData;
	$arrayResult["IMG"] = $arrayGroupData;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>