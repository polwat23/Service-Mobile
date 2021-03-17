<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$imgPaht = null;
	$imgUrl = null;
	
	$img = $dataComing["img"];
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/banner/';
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
				mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($img,$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$imgUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/banner/".$createImage["normal_path"];
				$imgPaht = "resource/gallery_web_coop/banner/".$createImage["normal_path"];
			
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
		
	}
	
	$insertBanner = $conmysql->prepare("INSERT INTO webcoopbanner(
													news_id,
													img_path,
													img_url,
													type,
													create_by
													)
										  VALUES (
													:news_id,
													:img_path,
													:img_url,
													:type,
													:create_by)");	
	if($insertBanner->execute([
			':news_id' =>  $dataComing["news_id"],
			':img_path' =>  $imgPaht,
			':img_url' =>  $imgUrl,
			':type' =>  $dataComing["type"],
			':create_by' =>  $payload["username"]	
	])){
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
		
	
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
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
