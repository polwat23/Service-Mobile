<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','submenuknowledge_id'],$dataComing)){
	$fileUrl = null;
	$imgUrl = null;
	$fileData = $dataComing["file"];
	$file = $fileData[0];
	
	if(isset($dataComing["file"]) && $dataComing["file"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/';
		$file_name = $file["name"];
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_pdf($file["file"],$file_name,$destination,null);
		if($createImage){
			$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$createImage["normal_path"];
		
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}
	
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/'.$forderAcc;
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($dataComing["img"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$imgUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$forderAcc."/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}

	$detail_html = '<!DOCTYPE HTML>
							<html>
							<head>
						  <meta charset="UTF-8">
						  <meta name="viewport" content="width=device-width, initial-scale=1.0">
						  '.$dataComing["html_root_"].'
						  </body>
							</html>';
	
		
	$insert_file = $conmysql->prepare("INSERT INTO webcoopknowledgebase(
										submenuknowledge_id,
                                        title,
										detail,
										img_url,
										file_url,
										create_by,
										update_by)
									VALUES(
										:submenuknowledge_id,
                                        :title,
										:detail,
										:img_url,
										:file_url,
										:create_by,
										:update_by
									)");
	if($insert_file->execute([
		':submenuknowledge_id' =>  $dataComing["submenuknowledge_id"],
		':title' =>  $dataComing["title"],
		':detail' =>  $detail_html,
		':img_url' => $imgUrl ?? null,
		':file_url' => $fileUrl ?? null,
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
		
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
							
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['dataComing'] = [
		':submenuknowledge_id' =>  $dataComing["submenuknowledge_id"],
		':title' =>  $dataComing["title"],
		':detail' =>  $detail_html,
		':img_url' => $img_url ?? null,
		':file_url' => $fileUrl ?? null,
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	];
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