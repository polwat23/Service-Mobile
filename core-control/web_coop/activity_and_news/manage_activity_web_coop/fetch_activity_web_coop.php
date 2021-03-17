<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	$fetchNewsWebCoop = $conmysql->prepare("SELECT
												webcoopactivity_id,
												id_gallery,
												activity_title,
												activity_detail,
												create_date,
												update_date,
												create_by
											FROM
												webcoopactivity
											WHERE is_use != '-9'
											ORDER BY
												update_date
											DESC");
	$fetchNewsWebCoop->execute();
	while($rowNewsWebCoop = $fetchNewsWebCoop->fetch(PDO::FETCH_ASSOC)){
		$img_head_group = [];
		$arrNewsWebCoop = array();
		$fetchHeadImgNews = $conmysql->prepare("SELECT
													gallery_name,
													img_gallery_url,
													img_gallery_path
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
			$img_head_web_news["index"]= $rowNewsWebCoop["id_gallery"];
			$img_head_web_news["img_gallery_url"]=$arrNewsHeadImg["img_gallery_url"];
			$img_head_web_news["img_gallery_path"]=$arrNewsHeadImg["img_gallery_path"];
			$img_head_web_news["status"]="old";	
			$img_head_group[]=$img_head_web_news;
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
		$arrayGroupFile=[];
		while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
			
			$arrNewsFile = array();	
			$arrNewsFile["index"] = $rowFile["id_webcoopfile"];
			$arrNewsFile["img_path"] = $rowFile["file_patch"];
			$arrNewsFile["img_url"] = $rowFile["file_url"];
			$arrNewsFile["status"] = "old";
			$arrNewsFile["original"]=$rowFile["file_url"];
			$arrNewsFile["thumbnail"]=$rowFile["file_url"];
			$arrayGroupFile[] = $arrNewsFile;
		}
		$arrNewsWebCoop["ACTIVITY_ID"] = $rowNewsWebCoop["webcoopactivity_id"];
		$arrNewsWebCoop["ACTIVITY_NAME"] = $gallery_name;
		$arrNewsWebCoop["ACTIVITY_TITLE"] = $rowNewsWebCoop["activity_title"];
		$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
		$arrNewsWebCoop["ACTIVITY_DETAIL"] = $rowNewsWebCoop["activity_detail"];
		$arrNewsWebCoop["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
		$arrNewsWebCoop["CREATE_BY"] = $rowNewsWebCoop["create_by"];
		$arrNewsWebCoop["IMG"] = $arrayGroupFile;
		$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
		$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrNewsWebCoop;
	}
	$arrayResult["ACTIVITY_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>