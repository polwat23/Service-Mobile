<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$fetchHeadImgNews = $conmysql->prepare("SELECT
													img_gallery_url,
													img_gallery_path
												FROM
													webcoopgallary
												WHERE id_gallery = :id_gallery
												");
	$fetchHeadImgNews->execute([
			':id_gallery' => $dataComing["id_gallery"]
	]);
	$arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC);
	
	$imgPath = $arrNewsHeadImg["img_gallery_path"];
	$del_file="../../../../".$imgPath;
	
	foreach($dataComing["img_head"] as $head_img){
		if($head_img["status"]=="old"){
			$urlImgHead = $head_img["url"];
			$pathImgHead = $head_img["path"];
		}else{
			
			if(isset($dataComing["img_head"]) && $dataComing["img_head"] != null){
			$delIMg = unlink($del_file);
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/service';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($head_img["img"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
					if($createImage){
						$urlImgHead = $config["URL_SERVICE"]."resource/gallery_web_coop/service/".$createImage["normal_path"];
						$pathImgHead = "resource/gallery_web_coop/service/".$createImage["normal_path"];
						
					}else{
						$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}
		}
	}
	foreach($dataComing["file"] as $file_pdf){
		if($file_pdf["status"]=="old"){
			$fileUrl = $file_pdf["url"];
			$filePath = $file_pdf["path"];
			$fileName = $file_pdf["file_name"];
					
		}else{
			if(isset($dataComing["file"]) && $dataComing["file"] != null){
				$del_file="../../../../".$file_pdf["path"];
				$delfile=unlink($del_file);
			
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/service/';
				$file_name = $file_pdf["file_name"];
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_pdf($file_pdf["file"],$file_name,$destination,null);
				if($createImage){
					$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/service/".$createImage["normal_path"];
					$filePath = "resource/gallery_web_coop/service/".$createImage["normal_path"];
					$fileName = $file_pdf["file_name"];
				}else{
					$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}	
		}
			
	}
	if(isset($dataComing["html_root_"])){
	$detail_html = '<!DOCTYPE HTML>
							<html>
							<head>
						  <meta charset="UTF-8">
						  <meta name="viewport" content="width=device-width, initial-scale=1.0">
						  '.$dataComing["html_root_"].'
						  </body>
							</html>';
	}

	
	$update_service_webcoop = $conmysql->prepare("UPDATE webcoopservice SET 
													title = :title, 
													detail = :detail, 
													id_gallery = :id_gallery, 
													create_by = :create_by,
													groupservice_id = :type
												WHERE service_id = :id
											");
	if($update_service_webcoop->execute([
		':id' =>  $dataComing["id"],
		':title' =>  $dataComing["title"],
		':detail' =>   $detail_html,
		':id_gallery' =>  $dataComing["id_gallery"],
		':create_by' =>  $payload["username"],
		':type' =>  $dataComing["type"]

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
			':gallery_name' =>  $dataComing["news_title"],
			':img_gallery_url' =>  $urlImgHead,
			':img_gallery_path' => $pathImgHead,
			':create_by' =>  $dataComing["username"]

			])){
				$files = $dataComing["file"];
				$filesGroup = $files[0];
				$fileStatus = $filesGroup["status"];
				if($fileStatus=="old"){
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
					
				}else{
					
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
								
								
								$arrayResult["payload"] = $_SERVER;
								echo json_encode($arrayResult);
							
							}else{
								$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา ";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
								
						}else{
							
							$arrOldFile=$dataComing[old_file];
							$OldFile = $arrOldFile[0];
							$del_File="../../../../".$OldFile["path"];;
							$delFilePdf=unlink($del_File);
			
							$arrayResult['RESULT'] = TRUE;
							$arrayResult['delFilePdf'] = $delFilePdf;
							echo json_encode($arrayResult);
						}						
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
						$arrayResult['RESULT'] = FALSE;
						
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
				$arrayResult['RESULT'] = FALSE;
				
				echo json_encode($arrayResult);
				exit();
			}	
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทข่าวสารได้ กรุณาติดต่อผู้พัฒนา 1  ";
		
		$arrayResult['dataComing'] = $dataComing;
		$arrayResult['payload'] = $payload["username"];
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