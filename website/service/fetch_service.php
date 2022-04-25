<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','type'],$dataComing)){
	
		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		$fetchDeposit = $conmysql->prepare("
												SELECT
													service_id,
													title,
													detail,
													id_gallery,
													groupservice_id,
													create_date,
													update_date,
													create_by
												FROM
													webcoopservice
												WHERE
													is_use = '1' AND groupservice_id = :type
												ORDER BY
													create_date
												DESC");
		$fetchDeposit->execute(['type' => $dataComing['type']]);
		while($rowNewsWebCoop = $fetchDeposit->fetch(PDO::FETCH_ASSOC)){
			$img_head_group = [];
			$arrNewsWebCoop = array();
			$fetchHeadImgNews = $conmysql->prepare("SELECT
														gallery_name,
														img_gallery_url
													FROM
														webcoopgallary
													WHERE id_gallery = :id_gallery
													");
			$fetchHeadImgNews->execute([
				':id_gallery' => $rowNewsWebCoop["id_gallery"]
			]);
			$arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC);
			$img_head_web_news = [];
			$gallery_name = null;
			if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
				
				$img_head_web_news = $arrNewsHeadImg["img_gallery_url"];
				$img_head_group = $img_head_web_news;
				$gallery_name = $arrNewsHeadImg["gallery_name"];
			}else{
				$img_head_group=[];
			}
			
			
			$fetchNewsFileCoop = $conmysql->prepare("SELECT
													id_webcoopfile,
													file_patch,
													file_url
												FROM
													webcoopfiles
												WHERE
													id_gallery  = :id_gallery ");
			$fetchNewsFileCoop->execute([
				':id_gallery' => $rowNewsWebCoop["id_gallery"]
			]);
			$arrNewsWebCoop["SERVICE_ID"] = $rowNewsWebCoop["service_id"];
			$arrNewsWebCoop["ACTIVITY_NAME"] = $gallery_name;
			$arrNewsWebCoop["TITLE"] = $rowNewsWebCoop["title"];
			$arrNewsWebCoop["TYPE"] = $rowNewsWebCoop["type"];
			$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
			$arrNewsWebCoop["DETAIL"] = $rowNewsWebCoop["detail"];
			$arrNewsWebCoop["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
			$arrNewsWebCoop["CREATE_BY"] = $rowNewsWebCoop["create_by"];
			$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
			$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
			$arrayGroup[] = $arrNewsWebCoop;
		}
		$arrayResult["DEPOSIT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);


}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>