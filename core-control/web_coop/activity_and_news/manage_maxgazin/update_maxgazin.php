<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managemagazin')){
		
		$fetchOldFile = $conmysql->prepare("SELECT
												file_patch
											FROM
												webcoopfiles
											WHERE
												id_gallery = :id_gallery ");
		$fetchOldFile->execute([
			':id_gallery' => $dataComing["id_gallery"]
		]);
		$arrOldPath = $fetchOldFile->fetch(PDO::FETCH_ASSOC);
		
		
		foreach($dataComing["img_head"] as $head_img){
			if($head_img["status"]=="old"){
				$urlImgHead = $head_img["url"];
				$pathImgHead = $head_img["path"];
			}else{
				
				if(isset($dataComing["img_head"]) && $dataComing["img_head"] != null){
				$del_headfile="../../../../".$head_img["path"];
				$del=unlink($del_headfile);
				
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/maxgazins';
				$file_name = $lib->randomText('all',6);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_img($head_img["img"],$file_name,$destination,null);
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}else{
						if($createImage){
							$urlImgHead = $config["URL_SERVICE"]."resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
							$pathImgHead = "resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
							
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}
				}
			}
		}
		$fileUrl = null;
		$filePath = null;
		$fileName = null;
		foreach($dataComing["file"] as $file_pdf){
			if($file_pdf["status"]=="old"){
					$fileUrl = $file_pdf["url"];
					$filePath = $file_pdf["path"];
					$fileName = $file_pdf["file_name"];
						
			}else{
					if(isset($dataComing["file"]) && $dataComing["file"] != null){
						$del_file="../../../../".$file_pdf["path"];
						$delfile=unlink($del_file);
					
						$destination = __DIR__.'/../../../../resource/gallery_web_coop/maxgazins/';
						$file_name = $file_pdf["file_name"];
						if(!file_exists($destination)){
							mkdir($destination, 0777, true);
						}
						$createImage = $lib->base64_to_pdf($file_pdf["file"],$file_name,$destination,null);
						if($createImage){
							$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
							$filePath = "resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
							$fileName = $file_pdf["file_name"];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}
					
			}
				
		}

	
		$update_maxgazin = $conmysql->prepare("UPDATE webcoopmaxgazin SET 
														name = :name, 
														url = :url, 
														create_by = :create_by
												
													WHERE id_maxgazin = :id_maxgazin
												");
		if($update_maxgazin->execute([
			':name' =>  $dataComing["name"],
			':url' =>  $dataComing["url"],
			':create_by' =>  $payload["username"],
			':id_maxgazin' =>  $dataComing["id_maxgazin"]

		])){
		
		
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
				':img_gallery_url' =>  $urlImgHead,
				':img_gallery_path' => $pathImgHead,
				':create_by' =>  $payload["username"]

				])){
					
					$deletFile = $conmysql->prepare("DELETE FROM webcoopfiles WHERE id_gallery = :id_gallery");
					if($deletFile->execute([
						':id_gallery' =>  $dataComing["id_gallery"]
					])){
						
						if(isset($dataComing["file"]) && $dataComing["file"] != null){
							$name = explode('/',$filePath);
							$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url,file_name)
											VALUES(:id_gallery,:file_patch,:file_url,:file_name)");
							if($insert_gallery->execute([
							':id_gallery' =>  $dataComing["id_gallery"],
							':file_patch' =>  $filePath,
							':file_url' =>  $fileUrl,
							':file_name' =>  $fileName
							
							])){
								$arrayResult["RESULT"] = TRUE;
								$arrayResult["dfdfdf"] = $name[3];
								
								echo json_encode($arrayResult);
							}else{
								$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา ";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
								
						}else{
							
							$del_File="../../../../".$arrOldPath["file_patch"];
							$delFile=unlink($del_File);
							
							
							$arrayResult["del"] = $delFile;
							$arrayResult['RESULT'] = TRUE;
							echo json_encode($arrayResult);
						}
						
					
													
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา  ";
						$arrayResult['RESULT'] = FALSE;
						
						echo json_encode($arrayResult);
						exit();
					}
				
							
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา  ";
					$arrayResult['RESULT'] = FALSE;
					
					echo json_encode($arrayResult);
					exit();
				}	
				
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถอัพ กรุณาติดต่อผู้พัฒนา  ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
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