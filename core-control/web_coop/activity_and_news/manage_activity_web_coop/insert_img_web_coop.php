<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$urlImg = array();
	$pathImg = array();
	$fetchIdGallery = $conmysql->prepare("SELECT
											id_gallery
										FROM
											webcoopgallary
										ORDER BY id_gallery DESC
										LIMIT 1
													");
	$fetchIdGallery->execute([':gallery_name' => $dataComing["activity_title"]]);
	$id_Gallery = $fetchIdGallery->fetch(PDO::FETCH_ASSOC);	
	
	$ID = (int)$id_Gallery["id_gallery"];
	$forderAcc = $ID+1;
			
	$img_news = $dataComing["img"];
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		foreach($img_news as $file64){
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/activity/'.$forderAcc;
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($file64,$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$urlImg["url"] = $config["URL_SERVICE"]."resource/gallery_web_coop/activity/".$forderAcc."/".$createImage["normal_path"];
					$urlImg["path"] = "resource/gallery_web_coop/activity/".$forderAcc."/".$createImage["normal_path"];
					$groupImg[]=$urlImg;
				}else{
					$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
	}
	foreach($groupImg as $path_file){
		$InsertPatchFile[] = "('".$dataComing["id_gallery"]."','".$path_file["path"]."','".$path_file["url"]."')";
	}				
	$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url)
										VALUES".implode(',',$InsertPatchFile));						
	if($insert_gallery->execute()){
		$arrayResult["RESULT"] = TRUE;
		
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	} 
		
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>