<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','name'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
		$pathImg = null;
		
		if(isset($dataComing["img"]) && $dataComing["img"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
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
					$pathImg = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		
		
		if($dataComing['name']=='{name: "path_img_1"}'){
		
			$update_gallary	= $conmysql->prepare("UPDATE gcgallery SET 
													path_img_1 = :path_img
												WHERE id_gallery = :id_gallery");
			if($update_gallary->execute([
				':id_gallery' =>  $dataComing["id_gallery"],
				':path_img' =>  $pathImg ?? null
			
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดท gallary ได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			
		} else if($dataComing['name']=='path_img_2'){
		
			
			$update_gallary	= $conmysql->prepare("UPDATE gcgallery SET 
													path_img_2 = :path_img
												WHERE id_gallery = :id_gallery");
			if($update_gallary->execute([
				':id_gallery' =>  $dataComing["id_gallery"],
				':path_img' =>  $pathImg ?? null
			
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดท gallary ได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			//unlink($dataComing['img_path']);
			
			
		}else if($dataComing['name']=='path_img_3'){
		
			$update_gallary	= $conmysql->prepare("UPDATE gcgallery SET 
													path_img_3 = :path_img
												WHERE id_gallery = :id_gallery");
			if($update_gallary->execute([
				':id_gallery' =>  $dataComing["id_gallery"],
				':path_img' =>  $pathImg ?? null
			
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดท gallary ได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			
		}else if($dataComing['name']=='path_img_4'){
		
			$update_gallary	= $conmysql->prepare("UPDATE gcgallery SET 
													path_img_4 = :path_img
												WHERE id_gallery = :id_gallery");
			if($update_gallary->execute([
				':id_gallery' =>  $dataComing["id_gallery"],
				':path_img' =>  $pathImg ?? null
			
			])){
				$arrayResult["RESULT"] = TRUE;
				$arrayResult['RESPONSE'] = "สำเร็จ";
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดท gallary ได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			
		}else if($dataComing['name']=='path_img_5'){
		
			$update_gallary	= $conmysql->prepare("UPDATE gcgallery SET 
													path_img_5 = :path_img
												WHERE id_gallery = :id_gallery");
			if($update_gallary->execute([
				':id_gallery' =>  $dataComing["id_gallery"],
				':path_img' =>  $pathImg ?? null
			
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดท gallary ได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			
		}
		else{
			$arrayResult['RESPONSE_MESSAGE'] = " กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
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