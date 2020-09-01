<?php
require_once('autoload.php');

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
				$arrNewsFile["url"]=$rowFile["file_url"];
				$arrayGroupFile[]=$arrNewsFile;
		}
			
		$arrBannerWebCoop["GALLERY_ID"] = $rowNewsWebCoop["id_gallery"];
		$arrBannerWebCoop["GALLERY_NAME"] =  $rowNewsWebCoop["gallery_name"];
		$arrBannerWebCoop["IMG"] = $arrayGroupFile;
		$arrayGroup[] = $arrBannerWebCoop;
		
		$arrayResult["BANNER_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
?>