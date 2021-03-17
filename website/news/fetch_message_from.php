<?php
require_once('../autoload.php');
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	$fetchNewsWebCoop = $conmysql->prepare("SELECT
										id_messagefrompesident,
										title,
										detail_html,
										create_date,
										update_date,
										id_gallery,
										create_by
									FROM
										messagefrompresident
									WHERE is_use = '1'
									ORDER BY
										update_date
									DESC");
	$fetchNewsWebCoop->execute();
	while($rowNewsWebCoop = $fetchNewsWebCoop->fetch(PDO::FETCH_ASSOC)){
		$img_head_group = [];
		$arrMessageFromPresident = array();
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
		
		$fetchMessageFromPresident = $conmysql->prepare("SELECT
												id_webcoopfile,
												file_patch,
												file_url,
												file_name
											FROM
												webcoopfiles
											WHERE
												id_gallery  = :id_gallery ");
		$fetchMessageFromPresident->execute([
			':id_gallery' => $rowNewsWebCoop["id_gallery"]
		]);
		$arrayGroupFile=[];
		while($rowFile = $fetchMessageFromPresident->fetch(PDO::FETCH_ASSOC)){
			
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
		
		$arrMessageFromPresident["ID"] = $rowNewsWebCoop["id_messagefrompesident"];
		$arrMessageFromPresident["TITLE"] = $rowNewsWebCoop["title"];
		$arrMessageFromPresident["IMG_HEAD"] = $img_head_group;
		$arrMessageFromPresident["NEWS_DETAIL"] = $rowNewsWebCoop["detail"];
		$arrMessageFromPresident["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
		$arrMessageFromPresident["CREATE_BY"] = $rowNewsWebCoop["create_by"];
		$arrMessageFromPresident["HTML"] = $rowNewsWebCoop["detail_html"];
		$arrMessageFromPresident["NEW_FILE_PATC"] = $arrayGroupFile;
		$arrMessageFromPresident["TAG"] = $groupTag;
		$arrMessageFromPresident["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
		$arrMessageFromPresident["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrMessageFromPresident;
	}
	$arrayResult["MESSAGE_PRESIDENT_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

?>