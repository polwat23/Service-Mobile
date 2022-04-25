<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	$fetchFileFormCoop = $conmysql->prepare("SELECT 
												file_form_id,
												file_name,
												id_gallery,
												create_by,
												create_date,
												update_date
											FROM webcoop_file_form
											WHERE type = '2'
											ORDER BY file_form_id ASC");
	$fetchFileFormCoop->execute();
	while($rowFileForm = $fetchFileFormCoop->fetch(PDO::FETCH_ASSOC)){
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
			':id_gallery' => $rowFileForm["id_gallery"]
		]);
		$arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC);
		$img_head_web_news = [];
		$gallery_name = null;
		
		if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
			
			$img_head_web_news["uid"]= $rowFileForm["id_gallery"];
			$img_head_web_news["name"]= $rowFileForm["file_name"];
			$img_head_web_news["index"]= $rowFileForm["id_gallery"];
			$img_head_web_news["url"]=$arrNewsHeadImg["img_gallery_url"];
			$img_head_web_news["thumbUrl"]=$arrNewsHeadImg["img_gallery_url"];
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
												file_name,
												file_patch,
												file_url
											FROM
												webcoopfiles
											WHERE
												id_gallery  = :id_gallery ");
		$fetchNewsFileCoop->execute([
			':id_gallery' => $rowFileForm["id_gallery"]
		]);
		$arrNewsWebCoop["FILE_ID"] = $rowFileForm["file_form_id"];
		$arrNewsWebCoop["FILE_NAME"] = $gallery_name;
		$arrNewsWebCoop["FILE_NAME_TITLE"] = $rowFileForm["file_name"];
		$arrNewsWebCoop["PARENT_FILE"] = $img_head_group;
		$arrNewsWebCoop["ID_GALLERY"] = $rowFileForm["id_gallery"];
		$arrNewsWebCoop["CREATE_BY"] = $rowFileForm["create_by"];
		$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowFileForm["create_date"],'d m Y',true); 
		$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowFileForm["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrNewsWebCoop;
	}
	$arrayResult["FILE_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>