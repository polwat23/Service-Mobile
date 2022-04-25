<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','create_by'],$dataComing)){

	$urlImg = array();
	$pathImg = array();
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		$destination = __DIR__.'/../../resource/gallery_web_coop/Question/';
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
				$pathImgHeadNews = "resource/gallery_web_coop/Question/".$createImage["normal_path"];
				$urlImgHeadNews = $config["URL_SERVICE"]."/resource/gallery_web_coop/Question/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}


 $insert_webboard = $conmysql->prepare("INSERT INTO webcoopquestion(
															title,
															detail,
															create_by,
															email,
															img,
															member_no,
															avatar
														)
														VALUES(
															:title,
															:detail,
															:create_by,
															:email,
															:img,
															:member_no,
															:avatar
													
														)");
	if($insert_webboard->execute([
		':title' =>  $dataComing["title"]?? null,
		':detail' =>  $dataComing["html_root_"]?? null,
		':create_by' =>  $dataComing["create_by"]?? null,
		':email' =>  $dataComing["email"]?? null,
		':img' =>  $urlImgHeadNews,
		':member_no' =>  $dataComing["member_no"],
		':avatar' =>  $dataComing["avatar"]
		
		
	])){	

		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
							
	}else{
		
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
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