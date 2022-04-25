<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_partner','text_color'],$dataComing)){

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
		
		unlink($del_file);
		$updategallery = $conmysql->prepare("UPDATE 
										webcooppartner
									SET 
										img_url = :img_url,
										img_patch = :img_patch,
										name = :name,
										link = :link,
										text_color = :text_color,
										background_color = :background_color,
										create_by = :create_by,
										update_by = :update_by
									WHERE
										webcooppartner_id = :id_partner
									");
		if($updategallery->execute([
		':img_patch' =>  $pathImg,
		':img_url' =>  $urlImg,
		':id_partner' =>  $dataComing["id_partner"],
		':name' =>  $dataComing["name"],
		':link' =>  $dataComing["link"],
		':text_color' =>  $dataComing["text_color"],
		':background_color' =>  $dataComing["bg_color"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
		])){	
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);	
			
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทหน่วยงานได้ กรุณาติดต่อผู้พัฒนา  ";
			$arrayResult['DATA'] = [
		':img_patch' =>  $pathImg,
		':img_url' =>  $urlImg,
		':id_partner' =>  $dataComing["id_partner"],
		':name' =>  $dataComing["name"],
		':link' =>  $dataComing["link"],
		':text_color' =>  $dataComing["text_color"],
		':background_color' =>  $dataComing["bg_color"],
		':create_by' =>  $payload["username"]
		];
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
												create_by = :create_by,
												update_by = :update_by
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
		':update_by' =>  $payload["username"]

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
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>