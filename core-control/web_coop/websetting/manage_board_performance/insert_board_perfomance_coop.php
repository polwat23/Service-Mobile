<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$urlImg = array();
	$pathImg = array();
	if(isset($dataComing["img_head"]) && $dataComing["img_head"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/board_performance';
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
				$pathImgHead = "resource/gallery_web_coop/board_performance/".$createImage["normal_path"];
				$urlImgHead = $config["URL_SERVICE"]."resource/gallery_web_coop/board_performance/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
	
	if(isset($dataComing["detail_html_root_"])){
	$detail_html = '<!DOCTYPE HTML>
							<html>
							<head>
						  <meta charset="UTF-8">
						  <meta name="viewport" content="width=device-width, initial-scale=1.0">
						  '.$dataComing["detail_html_root_"].'
						  </body>
							</html>';
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
			':img_gallery_path' => $pathImgHead ?? null,
			':img_gallery_url' => $urlImgHead ?? null,
			':create_by' =>  $payload["username"]
	])){
		$fetchIdGallery = $conmysql->prepare("SELECT
													id_gallery
												FROM
													webcoopgallary
												WHERE gallery_name = :gallery_name
												");
		$fetchIdGallery->execute([':gallery_name' => $dataComing["title"]]);
		
		$id_Gallery = $fetchIdGallery->fetch(PDO::FETCH_ASSOC);	
		$insert_board_performance_web_coop = $conmysql->prepare("INSERT INTO webcoopboardperformance
													   (year,
														id_gallery,
														title,
														detail,
														create_by)
													VALUES
													   (:year,
														:id_gallery,
														:title,
														:detail,
														:create_by
														)");
		if($insert_board_performance_web_coop->execute([
			':year' =>  $dataComing["year"],
			':title' =>  $dataComing["title"],
			':detail' =>  $detail_html?? null,
			':id_gallery' =>  $id_Gallery["id_gallery"],
			':create_by' =>  $payload["username"]
		])){
			
			
			if(isset($dataComing["file"]) && $dataComing["file"] != null){
				$groupFile=[];
				$file_news = $dataComing["file"];
				foreach($file_news as $file64){
					$destination = __DIR__.'/../../../../resource/gallery_web_coop/board_performance/';
					$file_name = $file64["name"];
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					$createImage = $lib->base64_to_pdf($file64["file"],$file_name,$destination,null);
					if($createImage){
						$path_file["url"] = $config["URL_SERVICE"]."resource/gallery_web_coop/board_performance/".$createImage["normal_path"];
						$path_file["path"] = "resource/gallery_web_coop/board_performance/".$createImage["normal_path"];
						$path_file["name"] = $file_name;
						$groupFile[]=$path_file;
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
					foreach($groupFile as $path_file){
					$InsertPatchFile[] = "('".$id_Gallery["id_gallery"]."','".$path_file["path"]."','".$path_file["url"]."','".$path_file["name"]."')";
				}
				$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiles(id_gallery,file_patch,file_url,file_name)
										VALUES".implode(',',$InsertPatchFile));
				if($insert_gallery->execute()){
					$arrayResult["RESULT"] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}						
			}
			
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา  ";
			$arrayResult["dataComing"] = $dataComing;
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
								
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
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