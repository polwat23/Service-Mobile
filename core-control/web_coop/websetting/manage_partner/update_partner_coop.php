<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_partner','text_color'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managepartner')){
		if($dataComing["new_img"]=='true'){
		
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
			
			$fetchImgPath = $conmysql->prepare("SELECT  img_gallery_path
														FROM webcoopgallary
														WHERE 
														id_gallery = :id_gallery");
			$fetchImgPath->execute([':id_gallery' =>  $dataComing["id_gallery"]]);
			$ImgData = $fetchImgPath->fetch(PDO::FETCH_ASSOC);
			$imgPath = $ImgData["img_gallery_path"];
			$del_file="../../../../".$imgPath;

			$updategallery = $conmysql->prepare("UPDATE webcoopgallary SET 
														gallery_name = :gallery_name, 
														img_gallery_url = :img_gallery_url, 
														img_gallery_path = :img_gallery_path,
														create_by = :create_by
													WHERE id_gallery = :id_gallery
												");
				if($updategallery->execute([
				':id_gallery' =>  $dataComing["id_gallery"],
				':gallery_name' =>  $dataComing["name"],
				':img_gallery_url' =>  $urlImg,
				':img_gallery_path' => $pathImg,
				':create_by' =>  $payload["username"]

				])){	
					unlink($del_file);
					$updategallery = $conmysql->prepare("UPDATE 
													webcooppartner
												SET 
													name = :name,
													link = :link,
													text_color = :text_color,
													background_color = :background_color,
													create_by = :create_by
												WHERE
													webcooppartner_id = :id_partner
												");
				if($updategallery->execute([
				':id_partner' =>  $dataComing["id_partner"],
				':name' =>  $dataComing["name"],
				':link' =>  $dataComing["link"],
				':text_color' =>  $dataComing["text_color"],
				':background_color' =>  $dataComing["bg_color"],
				':create_by' =>  $payload["username"]

				])){	
					
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);	
					
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทหน่วยงานได้ กรุณาติดต่อผู้พัฒนา  ";
					$arrayResult['RESULT'] = FALSE;
					
					echo json_encode($arrayResult);
					exit();
				}	
					
					
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทหน่วยงานได้ กรุณาติดต่อผู้พัฒนา  ";
					$arrayResult['RESULT'] = FALSE;
					
					echo json_encode($arrayResult);
					exit();
				}				
		}else{
			$updategallery = $conmysql->prepare("UPDATE 
													webcooppartner
												SET 
													name = :name,
													link = :link,
													text_color = :text_color,
													background_color = :background_color,
													create_by = :create_by
												WHERE
													webcooppartner_id = :id_partner
												");
				if($updategallery->execute([
				':id_partner' =>  $dataComing["id_partner"],
				':name' =>  $dataComing["name"],
				':link' =>  $dataComing["link"],
				':create_by' =>  $payload["username"],
				':background_color' =>  $dataComing["bg_color"],
				':text_color' =>  $dataComing["text_color"],
				':create_by' =>  $payload["username"]

				])){	
					
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);	
					
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทหน่วยงานได้ กรุณาติดต่อผู้พัฒนา  ";
					$arrayResult['RESULT'] = FALSE;
					
					echo json_encode($arrayResult);
					exit();
				}				
		}
		
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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