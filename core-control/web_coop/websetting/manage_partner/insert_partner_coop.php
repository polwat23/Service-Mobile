<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','name'],$dataComing)){

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

	$insert_partner_webcoop = $conmysql->prepare("INSERT INTO webcooppartner
												(name,link,text_color, background_color,img_url,img_patch,create_by,update_by)
							                    VALUES
												(:name, :link, :text_color, :background_color,:img_url,:img_patch, :create_by, :update_by)");
	if($insert_partner_webcoop->execute([
		':name' =>  $dataComing["name"],
		':link' =>  $dataComing["link"],
		':background_color' =>  $dataComing["bg_color"],
		':text_color' =>  $dataComing["text_color"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"],
		':img_patch' =>  $pathImg,
		':img_url' =>  $urlImg
		
	])){
		
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
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
