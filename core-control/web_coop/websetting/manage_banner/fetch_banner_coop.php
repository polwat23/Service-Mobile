<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managebanner')){
		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		$fetchGalleryWebCoop = $conmysql->prepare("SELECT
													id_gallery,
													gallery_name,
													img_gallery_url,
													img_gallery_path
												 FROM
													webcoopgallary
												 WHERE
													gallery_name = 'banner'");
		$fetchGalleryWebCoop->execute();
		$rowNewsWebCoop = $fetchGalleryWebCoop->fetch(PDO::FETCH_ASSOC);
			
		$fetchImgBanner = $conmysql->prepare("SELECT
													id_webcoopfile,
													file_patch,
													file_url
												FROM
													webcoopfiles
												WHERE
													id_gallery  = :id_gallery ");
		$fetchImgBanner->execute([
				':id_gallery' => $rowNewsWebCoop["id_gallery"]
		]);
		$arrayGroupFile=[];		
		while($rowFile = $fetchImgBanner->fetch(PDO::FETCH_ASSOC)){
				$arrNewsFile["index"] = $rowFile["id_webcoopfile"];
				$arrNewsFile["original"]=$rowFile["file_url"];
				$arrNewsFile["thumbnail"]=$rowFile["file_url"];
				$arrayGroupFile[]=$arrNewsFile;
		}
			
		$arrBannerWebCoop["GALLERY_ID"] = $rowNewsWebCoop["id_gallery"];
		$arrBannerWebCoop["GALLERY_NAME"] =  $rowNewsWebCoop["gallery_name"];
		$arrBannerWebCoop["IMG"] = $arrayGroupFile;
		$arrayGroup = $arrBannerWebCoop;
		
		$arrayResult["ACTIVITY_DATA"] = $arrayGroup;
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