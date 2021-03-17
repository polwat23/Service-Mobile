<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$urlImg = [];
	$pathImg = [];
	if(isset($dataComing["img_head"]) && $dataComing["img_head"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/webboard';
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
				$pathImgHead = "resource/gallery_web_coop/webboard/".$createImage["normal_path"];
				$urlImgHead = $config["URL_SERVICE"]."resource/gallery_web_coop/webboard/".$createImage["normal_path"];
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
	
	  $insert_webboard = $conmysql->prepare("INSERT INTO webcoopwebboard(
															title,
															detail,
															img_url,
															img_path,
															create_by
														)
														VALUES(
															:title,
															:detail,
															:img_url,
															:img_path,
															:create_by
														)");
		if($insert_webboard->execute([
			':title' =>  $dataComing["title"]?? null,
			':detail' =>  $detail_html ?? null,
			':img_path' => $pathImgHead ?? null,
			':img_url' => $urlImgHead ?? null,
			':create_by' =>  $payload["username"]
		])){	
	
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
								
		}else{
			
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['pathImgHead'] = $pathImgHead;
			$arrayResult['urlImgHead'] = $urlImgHead;
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