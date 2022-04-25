<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	$fetchNewsWebCoop = $conmysql->prepare("SELECT 
												shopevent_id,
												title,
												detail,
												id_gallery,
												start_date,
												start_time,
												end_date,
												end_time,
												create_date,
												update_date,
												is_settime,
												create_by
											FROM webcoopshopevent
											WHERE is_use ='1' AND shopevent_id = :shopevent_id
											ORDER BY start_date DESC");
$fetchNewsWebCoop->execute([':shopevent_id' => $dataComing["shopevent_id"]]);
	while($rowShopEvent = $fetchNewsWebCoop->fetch(PDO::FETCH_ASSOC)){
		$img_head_group = [];
		$arrNewsWebCoop = array();
		$fetchHeadImgNews = $conmysql->prepare("SELECT
													img_gallery_url,
													img_gallery_path
												FROM
													webcoopgallary
												WHERE id_gallery = :id_gallery
												");
		$fetchHeadImgNews->execute([
			':id_gallery' => $rowShopEvent["id_gallery"]
		]);
		$arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC);
		$img_head_web_news = [];
		
		if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
			$img_head_web_news["url"]=$arrNewsHeadImg["img_gallery_url"];
			$img_head_group[]=$img_head_web_news;
		}else{
			$img_head_group=[];
		}
		
		$fetchNewsFileCoop = $conmysql->prepare("SELECT
												id_webcoopfile,
												file_patch,
												file_url,
												file_name
											FROM
												webcoopfiles
											WHERE
												id_gallery  = :id_gallery ");
		$fetchNewsFileCoop->execute([
			':id_gallery' => $rowShopEvent["id_gallery"]
		]);
		$arrayGroupFile=[];
		while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
			$arrNewsFile = array();	
			$arrNewsFile["url"] = $rowFile["file_url"];
			$arrayGroupFile[] = $arrNewsFile;
		}
		$arrFile = array();	
		$arrFile["url"] = $arrNewsHeadImg["img_gallery_url"];
		$arrayGroupFile[] = $arrFile;

		$arrNewsWebCoop["SHOPEVENT_ID"] = $rowShopEvent["shopevent_id"];
		$arrNewsWebCoop["TITLE"] = $rowShopEvent["title"];
		$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
		$arrNewsWebCoop["DETAIL"] = $rowShopEvent["detail"];
		$arrNewsWebCoop["ID_GALLERY"] = $rowShopEvent["id_gallery"];
		$arrNewsWebCoop["START_DATE"] = $lib->convertdate($rowShopEvent["start_date"],'d m Y',false); ;
		$arrNewsWebCoop["START_TIME"] = $rowShopEvent["start_time"];
		$arrNewsWebCoop["END_DATE"] = $lib->convertdate($rowShopEvent["end_date"],'d m Y',false);
		$arrNewsWebCoop["END_TIME"] = $rowShopEvent["end_time"];
		$arrNewsWebCoop["IS_SETTIME"] = $rowShopEvent["is_settime"] == '1' ? true : false;
		$arrNewsWebCoop["CREATE_BY"] = $rowShopEvent["create_by"];
		$arrNewsWebCoop["IMG_FILE"] = $arrayGroupFile;
		$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowShopEvent["create_date"],'d m Y',true); 
		$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowShopEvent["update_date"],'d m Y',true);  
		$arrayGroup = $arrNewsWebCoop;
	}
	$arrayResult["EVENT_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>