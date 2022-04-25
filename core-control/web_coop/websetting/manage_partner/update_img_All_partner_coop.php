<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$pathImg = null;
	$urlImg = null;
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/partner';
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($dataComing["img"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$pathImg = "resource/gallery_web_coop/partner/".$createImage["normal_path"];
				$urlImg = $config["URL_SERVICE"]."resource/gallery_web_coop/partner/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
	$updatePartner = $conmysql->prepare("UPDATE 
												webcooppartner
											SET 
												img_url = :img_url,
												img_patch = :img_patch
											");
		if($updatePartner->execute([
			':img_url' =>  $urlImg,
			':img_patch' =>  $pathImg

		])){	
			
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);	
			
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทหน่วยงานได้ กรุณาติดต่อผู้พัฒนา  ";
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