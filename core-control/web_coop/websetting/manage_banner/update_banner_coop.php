<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','banner_id'],$dataComing)){

	$img_path = null;
	$img_url = null;
	$img = $dataComing["img"];

	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		
		if($img["status"]=="old"){
			$img_path = $img["path"];
			$img_url = $img["url"];
			
		}else{
			
			$del_file="../../../../".$dataComing["old_file"];
			$del=unlink($del_file);
		
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/banner/';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($img["img"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$img_url= $config["URL_SERVICE"]."resource/gallery_web_coop/banner/".$createImage["normal_path"];
					$img_path = "resource/gallery_web_coop/banner/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					$arrayResult['dataComing'] = $dataComing;
					
					echo json_encode($arrayResult);
					exit();
				}
			}
			
			
		}
	}
	if($dataComing["type_link"] == "0"){
		
		$update_coop_banner = $conmysql->prepare("UPDATE
															webcoopbanner
														SET
															news_id = :news_id,
															type = :type,
															img_path = :img_path,
															img_url = :img_url
															
														WHERE
															banner_id = :banner_id
													");
		if($update_coop_banner->execute([
			':img_path' => $img_path??null,
			':img_url' =>  $img_url??null,
			':news_id' =>  $dataComing["news_id"],
			':type' =>  $dataComing["type"],
			':banner_id' =>  $dataComing["banner_id"]

		])){
				$arrayResult['RESULT'] = True;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['dataComing'] = [
				':img_path' => $img_path??NULL,
				':img_url' =>  $img_url??NULL,
				':news_id' =>  $dataComing["news_id"],
				':type' =>  $dataComing["type"],
				':banner_id' =>  $dataComing["banner_id"],
				':url' =>  $dataComing["url"]??NULL,
				':type_link' =>  $dataComing["type_link"]
			];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		
		$update_coop_banner = $conmysql->prepare("UPDATE
															webcoopbanner
														SET
															news_id = :news_id,
															type = :type,
															img_path = :img_path,
															img_url = :img_url,
															type_link = :type_link,
															url = :url
														WHERE
															banner_id = :banner_id
													");
		if($update_coop_banner->execute([
			':img_path' => $img_path??null,
			':img_url' =>  $img_url??null,
			':news_id' =>  $dataComing["news_id"],
			':type' =>  $dataComing["type"],
			':banner_id' =>  $dataComing["banner_id"],
			':type_link' =>  $dataComing["type_link"],
			':url' =>  $dataComing["url"]??NULL,

		])){
				$arrayResult['RESULT'] = True;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['dataComing'] = [
				':img_path' => $img_path??NULL,
				':img_url' =>  $img_url??NULL,
				':news_id' =>  $dataComing["news_id"],
				':type' =>  $dataComing["type"],
				':banner_id' =>  $dataComing["banner_id"],
				':url' =>  $dataComing["url"]??NULL,
				':type_link' =>  $dataComing["type_link"]
			];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>