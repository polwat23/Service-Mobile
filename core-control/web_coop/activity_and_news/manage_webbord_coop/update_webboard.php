<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_webboard'],$dataComing)){
	
	$head_img = $dataComing["head_img"];
	if($head_img["status"]=="old"){
		$urlImgHead = $head_img["url"];
		$pathImgHead = $head_img["path"];
	}else{
		
		if(isset($dataComing["head_img"]) && $dataComing["head_img"] != null){
		$del_headfile="../../../../".$head_img["old_img"];
		$del=unlink($del_headfile);
		
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/webboard';
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($head_img["img"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
				if($createImage){
					$urlImgHead = $config["URL_SERVICE"]."resource/gallery_web_coop/webboard/".$createImage["normal_path"];
					$pathImgHead = "resource/gallery_web_coop/webboard/".$createImage["normal_path"];
					
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
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
	$update_maxgazin = $conmysql->prepare("UPDATE webcoopwebboard SET 
													title = :title,
													detail = :detail,
													img_url = :img_url,
													img_path = :img_path,
													create_by = :create_by
												WHERE id_webboard = :id_webboard
											");
	if($update_maxgazin->execute([
		':title' =>  $dataComing["title"],
		':detail' => $detail_html,
		':img_url' => $urlImgHead,
		':img_path' => $pathImgHead,
		':create_by' =>  $payload["username"],
		':id_webboard' =>  $dataComing["id_webboard"]

	])){
	
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);	
			
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพ กรุณาติดต่อผู้พัฒนา  ";
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