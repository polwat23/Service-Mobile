<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		$fetchNewsWebCoop = $conmysql->prepare("SELECT
											id_webcoopnews,
											news_title,
											news_detail,
											news_html,
											create_date,
											update_date,
											id_gallery,
											create_by,
											tag
										FROM
											webcoopnews
										WHERE tag !='สารจากประธาน' AND is_use ='1'
										ORDER BY
											create_date
										DESC
										LIMIT 5"
										);
		$fetchNewsWebCoop->execute();
		while($rowNewsWebCoop = $fetchNewsWebCoop->fetch(PDO::FETCH_ASSOC)){
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
				':id_gallery' => $rowNewsWebCoop["id_gallery"]
			]);
			$arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC);
			$img_head_web_news = [];
			
			if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
				$img_head_web_news["index"]= $rowNewsWebCoop["id_gallery"];
				$img_head_web_news["img_gallery_url"]=$arrNewsHeadImg["img_gallery_url"];
				$img_head_web_news["img_gallery_path"]=$arrNewsHeadImg["img_gallery_path"];
				$img_head_web_news["status"]="old";	
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
				':id_gallery' => $rowNewsWebCoop["id_gallery"]
			]);
			$arrayGroupFile=[];
			while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
				
				$arrNewsFile = array();	
				$arrNewsFile["index"] = $rowFile["id_webcoopfile"];
				$arrNewsFile["path"] = $rowFile["file_patch"];
				$arrNewsFile["url"] = $rowFile["file_url"];
				$arrNewsFile["name"] = $rowFile["file_name"];
				$arrNewsFile["status"] = "old";
				$arrayGroupFile[] = $arrNewsFile;
			}
			$tag = explode(',',$rowNewsWebCoop["tag"]);
			$groupTag = [];
			$arr_tag = [];
			foreach($tag as $data){
				$arr_tag[]=$data;
				$groupTag=$arr_tag;
			}
			
			$arrNewsWebCoop["ID_NEWS"] = $rowNewsWebCoop["id_webcoopnews"];
			$arrNewsWebCoop["NEWS_TITLE"] = $rowNewsWebCoop["news_title"];
			$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
			$arrNewsWebCoop["NEWS_DETAIL"] = $rowNewsWebCoop["news_detail"];
			$arrNewsWebCoop["NEWS_DETAIL_SHORT"] = $lib->text_limit($rowNewsWebCoop["news_detail"],380);
			$arrNewsWebCoop["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
			$arrNewsWebCoop["CREATE_BY"] = $rowNewsWebCoop["create_by"];
			$arrNewsWebCoop["HTML"] = $rowNewsWebCoop["news_html"];
			$arrNewsWebCoop["NEW_FILE_PATC"] = $arrayGroupFile;
			$arrNewsWebCoop["TAG"] = $groupTag;
			$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
			$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
			$arrayGroup[] = $arrNewsWebCoop;
		}
		$arrayResult["NEWS_DATA"] = $arrayGroup;
		
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
		
	}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}

?>