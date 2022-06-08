<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$groupFileName = array();
	$file_news = $dataComing["file"];
	if(isset($dataComing["file"]) && $dataComing["file"] != null){
		foreach($file_news as $file64){
		$groupFileName[] = $file64["name"]; 
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/downloadfile/';
			$file_name = $file64["name"];
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_pdf($file64["file"],$file_name,$destination,null);
			if($createImage){
				$path_file["url"] = $config["URL_SERVICE"]."resource/gallery_web_coop/downloadfile/".$createImage["normal_path"];
				$path_file["path"] = "resource/gallery_web_coop/downloadfile/".$createImage["normal_path"];
				$path_file["name"] = $file_name;
				$groupFile[]=$path_file;
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}	
	}
	
	foreach($groupFile as $path_file){
		$InsertPatchFile[] = "('".$dataComing["groupdownload_id"]."','".$path_file["path"]."','".$path_file["url"]."','".$path_file["name"]."','".$payload["username"]."','".$payload["username"]."')";
	}
	$insert_gallery = $conmysql->prepare("INSERT INTO webcoopfiledownload(groupdownload_id,file_path,file_url,file_name,create_by,update_by)
					                      VALUES".implode(',',$InsertPatchFile));
						

	if($insert_gallery->execute()){
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['INSERTDAATA'] = $InsertPatchFile;
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