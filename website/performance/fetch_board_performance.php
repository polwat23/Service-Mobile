<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	
	
	$fetchYear = $conmysql->prepare("SELECT  DISTINCT
											year	
									FROM
											webcoopboardperformance
									ORDER BY
										year
									DESC");
	$fetchYear->execute();
	$yeargroup = [];
	while($rowYear = $fetchYear->fetch(PDO::FETCH_ASSOC)){
		$arrYear = "";
		$arrYear =  $rowYear["year"];
		$yeargroup[]=$arrYear;
	}
	if(isset($dataComing["year"]) && $dataComing["year"] != null){
		$s_year = $dataComing["year"];
	}else{
		$s_year = $yeargroup[0];
	}
	
	$fetchNewsWebCoop = $conmysql->prepare("SELECT
												id_boardperformance,
												year,
												id_gallery,
												title,
												detail,
												create_date,
												update_date,
												create_by
											FROM
												webcoopboardperformance
											WHERE 
												year = :year AND is_use = '1'
									ORDER BY
										update_date
									DESC");
	$fetchNewsWebCoop->execute([
		':year' => $s_year
	]);
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
		$img_head_web_news = "";
		
		if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
			$img_head_web_news = $arrNewsHeadImg["img_gallery_url"];
			$img_head_group = $img_head_web_news;
		}else{
			$img_head_group = "";
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
		$arrayGroupFile="";
		while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
			
			$arrNewsFile = "";	
			
			$arrNewsFile = $rowFile["file_url"];
	
			$arrayGroupFile = $arrNewsFile;
		}
		$tag = explode(',',$rowNewsWebCoop["tag"]);
		$groupTag = [];
		$arr_tag = [];
		foreach($tag as $data){
			$arr_tag[]=$data;
			$groupTag=$arr_tag;
		}
		
		$arrNewsWebCoop["ID_BOARDPERFERMANCE"] = $rowNewsWebCoop["id_boardperformance"];
		$arrNewsWebCoop["TITLE"] = $rowNewsWebCoop["title"];
		$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
		$arrNewsWebCoop["DETAIL"] = $rowNewsWebCoop["detail"];
		$arrNewsWebCoop["YEAR"] = $rowNewsWebCoop["year"];
		$arrNewsWebCoop["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
		$arrNewsWebCoop["CREATE_BY"] = $rowNewsWebCoop["create_by"];
		$arrNewsWebCoop["HTML"] = $rowNewsWebCoop["detail"];
		$arrNewsWebCoop["FILE"] = $arrayGroupFile;
	
		$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
		$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrNewsWebCoop;
	}
	


	
	$arrayResult["BOARD_PERFORMANCE_DATA"] = $arrayGroup;
	$arrayResult["YEAR_DATA"] = $yeargroup;
	$arrayResult["YEAR"] = $yeargroup[count($yeargroup)-1];
	
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>