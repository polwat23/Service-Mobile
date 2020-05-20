<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_news'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
		
		$pathImg1 = null;
		$pathImg2 = null;
		$pathImg3 = null;
		$pathImg4 = null;
		$pathImg5 = null;
		
		if(isset($dataComing["img_head_news"]) && $dataComing["img_head_news"] != null){
			$destination = __DIR__.'/../../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["img_head_news"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImgHeadNews = $config["URL_SERVICE"]."resource/gallery/".$createImage["normal_path"];
				}else{
					//$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					//$arrayResult['RESULT'] = FALSE;
					//echo json_encode($arrayResult);
					//exit();
					$pathImgHeadNews= $dataComing["img_head_news"];
				}
			}
		}
		
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
					//$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					//$arrayResult['RESULT'] = FALSE;
					
					//echo json_encode($arrayResult);
					//exit();
					$pathImg1 = $dataComing["img1"];
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
					//$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					//$arrayResult['RESULT'] = FALSE;
					//echo json_encode($arrayResult);
					//exit();
					$pathImg2 = $dataComing["img2"];
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
					//$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					//$arrayResult['RESULT'] = FALSE;
					//echo json_encode($arrayResult);
					//exit();
					$pathImg3 = $dataComing["img3"];
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
					//$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					//$arrayResult['RESULT'] = FALSE;
					//echo json_encode($arrayResult);
					//exit();
					$pathImg4 = $dataComing["img4"];
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
					//$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					//$arrayResult['RESULT'] = FALSE;
					//echo json_encode($arrayResult);
					//exit();
					$pathImg5 = $dataComing["img5"];
				}
			}
		}
		$detail_html = '<!DOCTYPE HTML>
								<html>
								<head>
							  <meta charset="UTF-8">
							  <meta name="viewport" content="width=device-width, initial-scale=1.0">
							  '.$dataComing["news_html_root_"].'
							  </body>
								</html>';
		

		$update_news= $conmysql->prepare("UPDATE gcnews SET 
												news_title = :news_title,
												news_detail = :news_detail,
												path_img_header=:path_img_header,
												link_news_more = :link_news_more,
												create_by = :create_by,
												img_gallery_1=:path_img_1,
												img_gallery_2=:path_img_2,
												img_gallery_3=:path_img_3,
												img_gallery_4=:path_img_4,
												img_gallery_5=:path_img_5,
												news_html = :news_html
										  WHERE id_news = :id_news");
			if($update_news->execute([
				':id_news' =>  $dataComing["id_news"],
				':news_title' =>  $dataComing["news_title"]?? null,
				':news_detail' =>  $dataComing["news_detail"] ?? null,
				':path_img_header' => $pathImgHeadNews ?? null,
				':link_news_more' =>  $dataComing["link_news_more"],
				':path_img_1' => $pathImg1 ?? null,
				':path_img_2' => $pathImg2 ?? null,
				':path_img_3' => $pathImg3 ?? null,
				':path_img_4' => $pathImg4 ?? null,
				':path_img_5' => $pathImg5 ?? null,
				':create_by' => $payload["username"],
				':news_html' => $detail_html
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
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