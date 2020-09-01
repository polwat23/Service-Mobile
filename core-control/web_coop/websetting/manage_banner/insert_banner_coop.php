<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managebanner')){
		$urlImg = array();
		$pathImg = array();
		
		$img_news = $dataComing["img"];
		if(isset($dataComing["img"]) && $dataComing["img"] != null){
			foreach($img_news as $file64){
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/banner/';
				$file_name = $lib->randomText('all',6);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_img($file64,$file_name,$destination,null);
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}else{
					if($createImage){
						$urlImg["url"] = $config["URL_SERVICE"]."resource/gallery_web_coop/banner/".$createImage["normal_path"];
						$urlImg["path"] = "resource/gallery_web_coop/activity/banner/".$createImage["normal_path"];
						$groupImg[]=$urlImg;
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}
		}
		
		$chkGallery = $conmysql->prepare("SELECT
											id_gallery
										FROM
											webcoopgallary
										WHERE gallery_name = :gallery_name ");
		$chkGallery->execute([
				':gallery_name' => "banner"
		]);
		$chkGallery->fetch(PDO::FETCH_ASSOC);
		
		if($chkGallery->rowCount() > 0){
			foreach($groupImg as $path_file){
				$InsertPatchFile[] = "('".$dataComing["id_gallery"]."','".$path_file["path"]."','".$path_file["url"]."')";
			}				
			$insert_img_banner = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url)
											VALUES".implode(',',$InsertPatchFile));						
			if($insert_img_banner->execute()){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['dfdf'] = $chkGallery;
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			} 
		}else{
			$insert_gallery = $conmysql->prepare("INSERT INTO webcoopgallary(
														gallery_name,
														create_by)
										      VALUES (
														'banner',
														:create_by)");	
			if($insert_gallery->execute([
				':create_by' =>  $payload["username"]
			])){
				$chkGallery = $conmysql->prepare("SELECT
											id_gallery
										FROM
											webcoopgallary
										WHERE gallery_name = :gallery_name ");
				$chkGallery->execute([
						':gallery_name' => "banner"
				]);
				$id_gallery=$chkGallery->fetch(PDO::FETCH_ASSOC);
				
				
				foreach($groupImg as $path_file){
					$InsertPatchFile[] = "('".$id_gallery["id_gallery"]."','".$path_file["path"]."','".$path_file["url"]."')";
				}				
				$insert_img_banner = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url)
												VALUES".implode(',',$InsertPatchFile));						
				if($insert_img_banner->execute()){
					$arrayResult["RESULT"] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
					$arrayResult['dfdf'] = $chkGallery;
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				} 
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
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
