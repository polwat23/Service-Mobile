<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
		$pathImg1 = null;
		$pathImg2 = null;
		$pathImg3 = null;
		$pathImg4 = null;
		$pathImg5 = null;
			if(isset($dataComing["img1"]) && $dataComing["img1"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img1"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg1 = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
					//$arrayResult['RESPONSE_MESSAGE'] = $pathImg1;
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		if(isset($dataComing["img2"]) && $dataComing["img2"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img2"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg2 = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		if(isset($dataComing["img3"]) && $dataComing["img3"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img3"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg3 = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		
		if(isset($dataComing["img4"]) && $dataComing["img4"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img4"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg4 = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		
		if(isset($dataComing["img5"]) && $dataComing["img5"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img5"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg5 = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		
		$insert_gallary	= $conmysql->prepare("INSERT INTO gcgallery(path_img_1,path_img_2,path_img_3,path_img_4,path_img_5,name_gallery,create_by)
											  VALUES(:path_img_1,:path_img_2,:path_img_3,:path_img_4,:path_img_5,:name_gallery,:create_by)");
			if($insert_gallary->execute([
				':path_img_1' => $pathImg1 ?? null,
				':path_img_2' => $pathImg2 ?? null,
				':path_img_3' => $pathImg3 ?? null,
				':path_img_4' => $pathImg4 ?? null,
				':path_img_5' => $pathImg5 ?? null,
				':name_gallery' =>  $dataComing["title"],
				':create_by' => $dataComing["create_by"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่ม gallary ได้ กรุณาติดต่อผู้พัฒนา ";
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