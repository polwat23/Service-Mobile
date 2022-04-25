
<?php

require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web = array();
		$fetchMessagefrom = $conmysql->prepare("
									SELECT
										id_messagefrompesident,
										title,
										detail_html,
										create_date,
										update_date,
										id_gallery,
										create_by
									FROM
										messagefrompresident
									WHERE is_use = '1' AND id_messagefrompesident = :news_id
									ORDER BY
										update_date
									DESC
											");
		$fetchMessagefrom->execute([
			':news_id' => $dataComing["news_id"]
		]);
		while($rowMessageFrom = $fetchMessagefrom->fetch(PDO::FETCH_ASSOC)){
			$img_head_group = [];
			$arrMessageFrom = array();
			$fetchHeadImg = $conmysql->prepare("SELECT
														img_gallery_url,
														img_gallery_path
													FROM
														webcoopgallary
													WHERE id_gallery = :id_gallery
													");
			$fetchHeadImg->execute([
				':id_gallery' => $rowMessageFrom["id_gallery"]
			]);
			$arrNewsHeadImg = $fetchHeadImg->fetch(PDO::FETCH_ASSOC);
			$img_head_web = [];
			
			if(isset($arrNewsHeadImg["img_gallery_url"]) && $arrNewsHeadImg["img_gallery_url"] != null){
				
				$img_head_web=$arrNewsHeadImg["img_gallery_url"];
				$img_head_group=$img_head_web;
			}else{
				$img_head_group="";
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
			$filePath='';
			$fileName='';
			while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
				
				$arrNewsFilePath = "";	
				$arrNewsName = "";
				$arrNewsFilePath = $rowFile["file_url"];
				$arrNewsName = $rowFile["file_name"];
				
				$filePath = $arrNewsFilePath;
				$fileName = $arrNewsName;
			}
			$tag = explode(',',$rowMessageFrom["tag"]);
			$groupTag = [];
			$arr_tag = [];
			foreach($tag as $data){
				$arr_tag[]=$data;
				$groupTag=$arr_tag;
			}
			$arrMessageFrom["ID_NEWS"] = $rowMessageFrom["id_messagefrompesident"];
			$arrMessageFrom["NEWS_TITLE"] = $rowMessageFrom["title"];
			$arrMessageFrom["IMG_HEAD"] = $img_head_group;
			$arrMessageFrom["ID_GALLERY"] = $rowMessageFrom["id_gallery"];
			$arrMessageFrom["CREATE_BY"] = $rowMessageFrom["create_by"];
			$arrMessageFrom["HTML"] = $rowMessageFrom["detail_html"];
			$arrMessageFrom["NEW_FILE_PATC"] = $filePath;
			$arrMessageFrom["NEW_FILE_NAME"] = $fileName;
			$arrMessageFrom["TAG"] = $groupTag;
			$arrMessageFrom["CREATE_DATE"] = $lib->convertdate($rowMessageFrom["create_date"],'d m Y',true); 
			$arrMessageFrom["UPDATE_DATE"] = $lib->convertdate($rowMessageFrom["update_date"],'d m Y',true);  
			$arrayGroup = $arrMessageFrom;
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

	