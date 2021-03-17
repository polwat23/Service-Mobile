<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$fileUrl = null;
	$filePath = null;
	$file_news = $dataComing["file"];
	if(isset($dataComing["file"]) && $dataComing["file"] != null){
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/file/';
			$file_name = $dataComing["file_name"];
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_pdf($file_news,$file_name,$destination,null);
			if($createImage){
				$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/file/".$createImage["normal_path"];
				$filePath = "resource/gallery_web_coop/file/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
	}
	$insert_file = $conmysql->prepare("INSERT INTO webcoopmeetingagenda(
															title,
															file_patch,
															file_url,
															detail,
															create_by,
															date
														)
														VALUES(
															:title,
															:file_patch,
															:file_url,
															:detail,
															:create_by,
															:date
														)");
	if($insert_file->execute([
			':title' =>  $dataComing["title"],
			':file_patch' => $filePath ?? null,
			':file_url' => $fileUrl ?? null,
			':detail' => $dataComing["detail"]?? '',
			':create_by' =>  $payload["username"],
			':date' =>  $dataComing["date"],
		])){
				$arrayResult['RESULT'] = True;
				echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
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