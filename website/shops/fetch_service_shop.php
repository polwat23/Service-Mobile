<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$arrayGroupFile = array();
	$arr_img_head = array();
	$arrGroupList = array();
	$fetchServiceShop = $conmysql->prepare("
										SELECT
											serviceshop_id,
											title,
											detail,
											id_gallery,
											create_date,
											update_date,
											create_by
										FROM
											webcoopserviceshop
										WHERE
											is_use != '-9'
									ORDER BY
										update_date
									DESC");
	$fetchServiceShop->execute();
	while($rowNewsWebCoop = $fetchServiceShop->fetch(PDO::FETCH_ASSOC)){
		$arrGroupList = [];
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
		$arr_img_head = [];
		
		if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
			$arr_img_head["index"]= $rowNewsWebCoop["id_gallery"];
			$arr_img_head["IMG_URL"]=$arrNewsHeadImg["img_gallery_url"];
			$arr_img_head["IMG_PATH"]=$arrNewsHeadImg["img_gallery_path"];
			$arr_img_head["original"]=$arrNewsHeadImg["img_gallery_url"];
			$arr_img_head["thumbnail"]=$arrNewsHeadImg["img_gallery_url"];
			
			$arr_img_head["status"]="old";	
			$img_head_group[]=$arr_img_head;
			$arrGroupList[] = $arr_img_head;
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
			
			$arrFile = array();	
			$arrFile["uid"] = $rowFile["id_webcoopfile"];
			$arrFile["path"] = $rowFile["file_patch"];
			$arrFile["img"] = $rowFile["file_url"];
			$arrFile["url"] = $rowFile["file_url"];
			$arrFile["name"] = $rowFile["file_name"];
			$arrFile["thumbnail"] = $rowFile["file_url"];
			$arrFile["original"] = $rowFile["file_url"];
			$arrFile["status"] = "old";
			$arrayGroupFile[] = $arrFile;
			$arrGroupList[] = $arrFile;
		}
		$arrNewsWebCoop["SERVICESHOP_ID"] = $rowNewsWebCoop["serviceshop_id"];
		$arrNewsWebCoop["TITLE"] = $rowNewsWebCoop["title"];
		$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
		$arrNewsWebCoop["DETAIL"] = $rowNewsWebCoop["detail"];
		$arrNewsWebCoop["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
		$arrNewsWebCoop["CREATE_BY"] = $rowNewsWebCoop["create_by"];
		$arrNewsWebCoop["FILE"] = $arrayGroupFile;
		$arrNewsWebCoop["FILE_LIST_SHOW"] = $arrGroupList;
	
		$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
		$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrNewsWebCoop;
	}
	$arrayResult["SERVICESHOP_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>