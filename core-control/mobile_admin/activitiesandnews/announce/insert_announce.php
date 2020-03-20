<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','announce')){
		$pathImg = null;
		if(isset($dataComing["announce_cover"]) && $dataComing["announce_cover"] != null){
			$destination = __DIR__.'/../../../../resource/announce';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["announce_cover"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$pathImg = $config["URL_SERVICE"]."resource/announce/".$createImage["normal_path"];
				}else{
					$pathImg= $dataComing["announce_cover"];
				}
			}
		}
		
		$insert_announce = $conmysql->prepare("INSERT INTO gcannounce(
											announce_cover,
											announce_title,
											announce_detail,
											effect_date,
											priority,
											flag_granted,
											username
										)
										VALUES(
											:announce_cover,
											:announce_title,
											:announce_detail,
											:effect_date,
											:priority,
											:flag_granted,
											:username
										)");
			if($insert_announce->execute([
				':announce_title' =>  $dataComing["announce_title"],
				':announce_detail' =>  $dataComing["announce_detail"],
				':effect_date' =>  $dataComing["effect_date"],
				':priority' =>  $dataComing["priority"],
				':flag_granted' =>  $dataComing["flag_granted"],
				':username' =>  $dataComing["username"],
				':announce_cover' =>  $pathImg??null
				

			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแจ้งประกาศได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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