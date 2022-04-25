<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_meeting'],$dataComing)){

	$fileUrl = null;
	$filePath = null;

	foreach($dataComing["file"] as $file_pdf){
		if($file_pdf["status"]=="old"){
			$fileUrl = $file_pdf["url"];
			$filePath = $file_pdf["path"];
			$fileName = $file_pdf["file_name"];
					
		}else{
			if(isset($dataComing["file"]) && $dataComing["file"] != null){
				$del_file="../../../../".$file_pdf["path"];
				$delfile=unlink($del_file);
			
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/news/';
				$file_name = $file_pdf["file_name"];
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_pdf($file_pdf["file"],$file_name,$destination,null);
				if($createImage){
					$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/news/".$createImage["normal_path"];
					$filePath = "resource/gallery_web_coop/news/".$createImage["normal_path"];
					$fileName = $file_pdf["file_name"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}	
		}
			
	}
	$insert_file = $conmysql->prepare("UPDATE webcoopmeetingagenda SET
															title = :title,
															file_patch = :file_patch,
															file_name = :file_name,
															file_url = :file_url,
															detail = :detail,
															create_by = :create_by,
															date = :date
														WHERE
															id_meettingagenda  = :id_meeting
														");
	if($insert_file->execute([
		':title' =>  $dataComing["title"],
		':file_name' => $fileName ?? null,
		':file_patch' => $filePath ?? null,
		':file_url' => $fileUrl ?? null,
		':detail' => $dataComing["detail"]?? '',
		':create_by' =>  $payload["username"],
		':date' =>  $dataComing["date"],
		':id_meeting' =>  $dataComing["id_meeting"]
		
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