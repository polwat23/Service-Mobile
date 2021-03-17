<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','name'],$dataComing)){

	$pathImg = null;
	$urlImg = null;
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/partner';
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($dataComing["img"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$pathImg = "resource/gallery_web_coop/partner/".$createImage["normal_path"];
				$urlImg = $config["URL_SERVICE"]."resource/gallery_web_coop/partner/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
	
	$insert_gallery = $conmysql->prepare("INSERT INTO webcoopgallary(
										gallery_name,
										img_gallery_path,
										img_gallery_url,
										create_by)
									VALUES(
										:gallery_name,
										:img_gallery_path,
										:img_gallery_url,
										:create_by
									)");
	if($insert_gallery->execute([
			':gallery_name' =>  $dataComing["name"],
			':img_gallery_path' => $pathImg ?? null,
			':img_gallery_url' => $urlImg ?? null,
			':create_by' =>  $payload["username"]
	])){	
			$fetchIdGallery = $conmysql->prepare("SELECT
														id_gallery
													FROM
														webcoopgallary
													WHERE gallery_name = :gallery_name
													");
			$fetchIdGallery->execute([':gallery_name' => $dataComing["name"]]);
			$id_Gallery = $fetchIdGallery->fetch(PDO::FETCH_ASSOC);	
			$dfdfdf="text_color = :text_color,
												background_color = :background_color,";
			$insert_partner_webcoop = $conmysql->prepare("INSERT INTO webcooppartner(name,link,id_gallery,text_color, background_color,create_by)
									VALUES(:name, :link, :id_gallery, :text_color, :background_color, :create_by)");
			if($insert_partner_webcoop->execute([
				':name' =>  $dataComing["name"],
				':link' =>  $dataComing["link"],
				':id_gallery' =>  $id_Gallery["id_gallery"],
				':background_color' =>  $dataComing["bg_color"],
				':text_color' =>  $dataComing["text_color"],
				':create_by' =>  $payload["username"]
			])){
				
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
								
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
