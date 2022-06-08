<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if(isset($dataComing["icon"]) && $dataComing["icon"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/'.$forderAcc;
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($dataComing["icon"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$icon = $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$forderAcc."/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}

	$inserGropudownload = $conmysql->prepare("INSERT INTO webcoopcoremenuknowledgebase(
										icon,
										coremenu_name,
										create_by,
										update_by)
									VALUES(
										:icon,
										:coremenu_name,
										:create_by,
										:update_by
									)");
	if($inserGropudownload->execute([
		':icon' =>  $icon,
		':coremenu_name' =>  $dataComing["menuname"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);

	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		$arrayResult['insertGropudownload'] = $insertGropudownload;
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

