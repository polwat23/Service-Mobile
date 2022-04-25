<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_heard = array();
	
	if(isset($dataComing["type"]) && $dataComing["type"] != null){
		if($dataComing["type"]=="0"){
			$fetchNewsWebCoop = $conmysql->prepare("SELECT
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
											WHERE is_use !='-9'
											ORDER BY
												create_date
											DESC");
			$fetchNewsWebCoop->execute();
		}else{
			$fetchNewsWebCoop = $conmysql->prepare("SELECT
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
											WHERE groupservice_id =:groupservice_id AND is_use !='-9'
											
											ORDER BY
												create_date
											DESC");
			$fetchNewsWebCoop->execute([':groupservice_id' =>  $dataComing["type"]]);
		}
	}else{
		$fetchNewsWebCoop = $conmysql->prepare("SELECT
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
											WHERE is_use !='-9'
											ORDER BY
												create_date
											DESC");
		$fetchNewsWebCoop->execute();
	}
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
		$img_heard = [];
		
		if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
			$img_heard["index"]= $rowNewsWebCoop["id_gallery"];
			$img_heard["img_gallery_url"]=$arrNewsHeadImg["img_gallery_url"];
			$img_heard["img_gallery_path"]=$arrNewsHeadImg["img_gallery_path"];
			$img_heard["status"]="old";	
			$img_head_group[]=$img_heard;
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

		$arrNewsWebCoop["SERVICE_ID"] = $rowNewsWebCoop["service_id"];
		$arrNewsWebCoop["TITLE"] = $rowNewsWebCoop["title"];
		$arrNewsWebCoop["IMG_HEAD"] = $img_head_group;
		$arrNewsWebCoop["DETAIL"] = $rowNewsWebCoop["detail"];
		$arrNewsWebCoop["GROUP"] = $rowNewsWebCoop["groupservice_id"];
		$arrNewsWebCoop["DETAIL_SHORT"] = $lib->text_limit($rowNewsWebCoop["detail"],480);
		$arrNewsWebCoop["ID_GALLERY"] = $rowNewsWebCoop["id_gallery"];
		$arrNewsWebCoop["CREATE_BY"] = $rowNewsWebCoop["create_by"];
		$arrNewsWebCoop["NEW_FILE_PATC"] = $arrayGroupFile;
		$arrNewsWebCoop["CREATE_DATE"] = $lib->convertdate($rowNewsWebCoop["create_date"],'d m Y',true); 
		$arrNewsWebCoop["UPDATE_DATE"] = $lib->convertdate($rowNewsWebCoop["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrNewsWebCoop;
	}
	$arrayResult["SERVICE_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>