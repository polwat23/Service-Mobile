<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
		$arrayGroup = array();
		$arrayGroupFile = array();
		$fetchMaxgazin = $conmysql->prepare("SELECT
													mz.id_maxgazin,
													mz.name,
													mz.url,
													mz.id_gallery,
													mz.create_by,
													mz.create_date,
													mz.update_date ,
													gall.img_gallery_url,
													gall.img_gallery_path
												FROM
													webcoopmaxgazin mz
												LEFT JOIN webcoopgallary gall
												ON mz.id_gallery = gall.id_gallery
												WHERE 
													mz.id_maxgazin =:maxgazin_id AND mz.is_use = :is_use
													");
		$fetchMaxgazin->execute([
			':maxgazin_id' => $dataComing["maxgazin_id"],
			':is_use' => '1'
		]);
		while($rowMaxgazin = $fetchMaxgazin->fetch(PDO::FETCH_ASSOC)){
			$name=explode('/',$rowMaxgazin["img_gallery_path"]);
			$arrImgHead = [];
			$imgHead = array();
			$fileGroup = [];
			
			$fetchNewsFileCoop = $conmysql->prepare("SELECT
														file_name,
														file_url,
														file_patch
												FROM
													webcoopfiles
												WHERE
													id_gallery  = :id_gallery ");
			$fetchNewsFileCoop->execute([
				':id_gallery' => $rowMaxgazin["id_gallery"]
			]);
				
		
			$arrayGroupFile=[];
			while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
				$arrNewsFile = [];	
				$arrNewsFile = $rowFile["file_url"];
				$arrayGroupFile = $arrNewsFile;
			}
			
			
			if(isset($rowMaxgazin["img_gallery_url"]) && $rowMaxgazin["img_gallery_url"] != null){
				$arrImgHead = $rowMaxgazin["img_gallery_url"];
				
				
				$imgHead=$arrImgHead;
			}
			$name = explode('/',$rowMaxgazin["img_gallery_path"]);
			
			$arrNewsWebCoop["ID_MAXGAZIN"] = $rowMaxgazin["id_maxgazin"];
			$arrNewsWebCoop["NAME"] = $rowMaxgazin["name"];
			$arrNewsWebCoop["URL"] = $rowMaxgazin["url"];
			$arrNewsWebCoop["ID_GALLERY"] = $rowMaxgazin["id_gallery"];
			$arrNewsWebCoop["CREATE_BY"] = $rowMaxgazin["create_by"];
			$arrNewsWebCoop["CREATE_DATE"] = $rowMaxgazin["create_date"];
			$arrNewsWebCoop["CREATE_DATE_FORMAT"] = $lib->convertdate($rowMaxgazin["create_date"],'d m Y',true); 
			$arrNewsWebCoop["IMG_HEAD"] = $imgHead;
			$arrNewsWebCoop["FILE"] = $arrayGroupFile;
			$arrayGroup = $arrNewsWebCoop;
		}
		$arrayResult["MAXGAZIN_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>