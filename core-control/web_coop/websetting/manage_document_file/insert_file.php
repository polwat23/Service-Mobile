<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managedocumentfile')){
		$fileUrl = null;
		$filePath = null;
		if($dataComing["type"]=="single"){
			$file_news = $dataComing["file"];
			if(isset($dataComing["file"]) && $dataComing["file"] != null){
					$destination = __DIR__.'/../../../../resource/gallery_web_coop/file/';
					$file_name = $dataComing["file_name"];
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					$createImage = $lib->base64_to_pdf($file_news,$file_name,$destination,null);
					if($createImage){
						$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/file/".$createImage["normal_path"];
						$filePath = "resource/gallery_web_coop/file/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
			}
		}else{
			$test = array();
			$file_news = $dataComing["file"];
			if(isset($dataComing["file"]) && $dataComing["file"] != null){
				$i = 1;
				foreach($file_news as $file64){
				$test[] = $file64["name"]; 
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/file/';
					$file_name = $file64["name"];
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					$createImage = $lib->base64_to_pdf($file64["file"],$file_name,$destination,null);
					if($createImage){
						$path_file["url"] = $config["URL_SERVICE"]."resource/gallery_web_coop/file/".$createImage["normal_path"];
						$path_file["path"] = "resource/gallery_web_coop/file/".$createImage["normal_path"];
						$path_file["name"] = $file_name;
						$groupFile[]=$path_file;
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
					$i++;
				
				}	
			}
		}
		if($dataComing["type_file"]=="parent"){

		  $insert_file = $conmysql->prepare("INSERT INTO webcoopgallary(
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
			if($insert_file->execute([
				':gallery_name' =>  $dataComing["file_name"],
				':img_gallery_path' => $filePath ?? null,
				':img_gallery_url' => $fileUrl ?? null,
				':create_by' =>  $payload["username"]
			])){
				$fetchIdGallery = $conmysql->prepare("SELECT
															id_gallery
														FROM
															webcoopgallary
														WHERE gallery_name = :gallery_name
														");
				$fetchIdGallery->execute([':gallery_name' => $dataComing["file_name"]]);
				$id_Gallery = $fetchIdGallery->fetch(PDO::FETCH_ASSOC);	
			
				$insert_news_web_coop = $conmysql->prepare("INSERT INTO webcoop_file_form(
																file_name,
																id_gallery,
																create_by)
															VALUES(
																:file_name,
																:id_gallery,
																:create_by)");
				if($insert_news_web_coop->execute([
					':file_name' =>  $dataComing["file_name"],
					':id_gallery' =>  $id_Gallery["id_gallery"],
					':create_by' =>  $payload["username"]

				])){
					if($dataComing["type"]=="single"){
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						foreach($groupFile as $path_file){
							$InsertPatchFile[] = "('".$id_Gallery["id_gallery"]."','".$path_file["path"]."','".$path_file["url"]."','".$path_file["name"]."')";
						}
						$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url,file_name)
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
					}
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา 1";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}					
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา2 ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			
		}else{
				foreach($groupFile as $path_file){
					$InsertPatchFile[] = "('".$dataComing["id_gallery"]."','".$path_file["path"]."','".$path_file["url"]."','".$path_file["name"]."')";
				}
					$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url,file_name)
												VALUES".implode(',',$InsertPatchFile));
					if($insert_gallery->execute()){
						$arrayResult["RESULT"] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE'] = "ไม่เพิ่มไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
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