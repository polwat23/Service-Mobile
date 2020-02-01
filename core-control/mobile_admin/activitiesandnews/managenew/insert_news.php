<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','image'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
	
			if(isset($dataComing["image"]) && $dataComing["image"] != null){
			$destination = __DIR__.'/../../../resource/gallery';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["image"],$file_name,$destination,null);
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
			
			
			
			
			/*
			'news_title','news_detail','path_img_header','link_news_more','id_gallery','create_by'
			$insertnews = $conmysql->prepare("INSERT INTO gcnews (news_title, news_detail, path_img_header, link_news_more, id_gallery, create_by) 
											  VALUES (:news_title, :news_detail, :path_img_header, :link_news_more, :id_gallery, :create_by);");
			if($insertnews->execute([
				':news_title' => $dataComing["news_title"],
				':news_detail' => $dataComing["news_detail"],
				':path_img_header' => $dataComing["path_img_header"],
				':link_news_more' => $dataComing["link_news_more"],
				':id_gallery' => $dataComing["id_gallery"],
				':create_by' => $dataComing["create_by"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			*/
		
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