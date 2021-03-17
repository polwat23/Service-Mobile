<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head = array();
	$fetchMessageFrom = $conmysql->prepare("
									SELECT
										id_messagefrompesident ,
										title,
										detail_html,
										create_date,
										update_date,
										id_gallery,
										create_by
									FROM
										messagefrompresident
										WHERE is_use !='-9'
									ORDER BY
										update_date
									DESC");
	$fetchMessageFrom->execute();
	while($rowMessageFrom = $fetchMessageFrom->fetch(PDO::FETCH_ASSOC)){
		$img_head_group = [];
		$arrMessageFrom = array();
		$fetchHeadImgNews = $conmysql->prepare("SELECT
													img_gallery_url,
													img_gallery_path
												FROM
													webcoopgallary
												WHERE id_gallery = :id_gallery
												");
		$fetchHeadImgNews->execute([
			':id_gallery' => $rowMessageFrom["id_gallery"]
		]);
		$arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC);
		$img_head = [];
		
		if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
			$img_head["index"]= $rowMessageFrom["id_gallery"];
			$img_head["img_gallery_url"]=$arrNewsHeadImg["img_gallery_url"];
			$img_head["img_gallery_path"]=$arrNewsHeadImg["img_gallery_path"];
			$img_head["status"]="old";	
			$img_head_group[]=$img_head;
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
			':id_gallery' => $rowMessageFrom["id_gallery"]
		]);
		$arrayGroupFile=[];
		while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
			
			$arrMessage = array();	
			$arrMessage["index"] = $rowFile["id_webcoopfile"];
			$arrMessage["path"] = $rowFile["file_patch"];
			$arrMessage["url"] = $rowFile["file_url"];
			$arrMessage["name"] = $rowFile["file_name"];
			$arrMessage["status"] = "old";
			$arrayGroupFile[] = $arrMessage;
		}
	
		$arrMessageFrom["ID_MESSAGEFROMPESIDENT"] = $rowMessageFrom["id_messagefrompesident"];
		$arrMessageFrom["TITLE"] = $rowMessageFrom["title"];
		$arrMessageFrom["IMG_HEAD"] = $img_head_group;
		$arrMessageFrom["ID_GALLERY"] = $rowMessageFrom["id_gallery"];
		$arrMessageFrom["CREATE_BY"] = $rowMessageFrom["create_by"];
		$arrMessageFrom["HTML"] = $rowMessageFrom["detail_html"];
		$arrMessageFrom["NEW_FILE_PATC"] = $arrayGroupFile;
		$arrMessageFrom["CREATE_DATE"] = $lib->convertdate($rowMessageFrom["create_date"],'d m Y',true); 
		$arrMessageFrom["UPDATE_DATE"] = $lib->convertdate($rowMessageFrom["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrMessageFrom;
	}
	$arrayResult["MESSAGE_FROM_DATA"] = $arrayGroup;
	$arrayResult["dataComing"] = $dataComing;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>