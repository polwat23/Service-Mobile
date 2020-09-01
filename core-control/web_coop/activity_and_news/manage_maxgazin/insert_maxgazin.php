<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managemagazin')){
		$urlImg = array();
		$pathImg = array();
		if(isset($dataComing["img_head"]) && $dataComing["img_head"] != null){
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/maxgazins';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img_head"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImgHeadNews = "resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
					$urlImgHeadNews = $config["URL_SERVICE"]."resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		$fileUrl = null;
		$filePath = null;
			$file_news = $dataComing["file"];
			if(isset($dataComing["file"]) && $dataComing["file"] != null){
					$destination = __DIR__.'/../../../../resource/gallery_web_coop/maxgazins/';
					$file_name = $dataComing["file_name"];
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					$createImage = $lib->base64_to_pdf($file_news,$file_name,$destination,null);
					if($createImage){
						$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
						$filePath = "resource/gallery_web_coop/maxgazins/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
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
				':gallery_name' =>  $dataComing["title"],
				':img_gallery_path' => $pathImgHeadNews ?? null,
				':img_gallery_url' => $urlImgHeadNews ?? null,
				':create_by' =>  $payload["username"],
			])){
				
				$fetchIdGallery = $conmysql->prepare("SELECT
															id_gallery
														FROM
															webcoopgallary
														WHERE gallery_name = :gallery_name AND img_gallery_url = :url
														");
				$fetchIdGallery->execute([':gallery_name' => $dataComing["title"],':url' => $urlImgHeadNews]);
				$id_Gallery = $fetchIdGallery->fetch(PDO::FETCH_ASSOC);	
				
				/*
				$arrayResult["RESULT"] = TRUE;
				$arrayResult["ID_GALLERY"] = $id_Gallery["id_gallery"];
				echo json_encode($arrayResult);
				*/
				//webcoopmaxgazin (name,url,id_gallery,create_by)
				$insertMaxgazin = $conmysql->prepare("INSERT INTO webcoopmaxgazin
																	(name, 
																	url,
																	id_gallery,
																	create_by)
															VALUES(
																:name, 
																:url, 
																:id_gallery, 
																:create_by
															)
														");
				if($insertMaxgazin->execute([
					':name' =>  $dataComing["title"],
					':url' =>  $dataComing["url"]??null,
					':id_gallery' =>  $id_Gallery["id_gallery"],
					':create_by' =>  $payload["username"]
				])){
					
					if(isset($dataComing["file"]) && $dataComing["file"] != null){
						$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url,file_name)
														VALUES(
															:id_gallery,
															:file_patch,
															:file_url,
															:file_name
														)
														
														");
						if($insert_gallery->execute([
								':id_gallery' =>  $id_Gallery["id_gallery"],
								':file_patch' =>  $filePath,
								':file_url' =>  $fileUrl,
								':file_name' =>  $dataComing["file_name"]
							])){
							$arrayResult["RESULT"] = TRUE;
							echo json_encode($arrayResult);
						}else{
							$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มวารสารได้ กรุณาติดต่อผู้พัฒนา ";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
						
					}else{
						$arrayResult["RESULT"] = TRUE;
						echo json_encode($arrayResult);
					}
					
				
				
					
					
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มวารสารได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				
									
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มวารสารได้ กรุณาติดต่อผู้พัฒนา ";
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