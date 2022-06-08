<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','id_submenu'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopsubknowledgebase
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											id_submenu = :id_submenu");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':id_submenu' => $dataComing["id_submenu"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		} 

	}else if($dataComing["type"] == 'update'){
		
		$img_path = null;
		$img_url = null;
		$img = $dataComing["img"];

		if(isset($dataComing["img"]) && $dataComing["img"] != null){
		
			if($img["status"]=="old"){
				$file_url = $img["url"];
				
			}else{
				
				
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/';
				$file_name = $lib->randomText('all',6);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_img($img["img"],$file_name,$destination,null);
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}else{
					if($createImage){
						$file_url= $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$createImage["normal_path"];
					
					}else{
						$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						$arrayResult['data'] = $img["img"];
						echo json_encode($arrayResult);
						exit();
					}
				}
				
		}
	}
		
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopsubknowledgebase
										SET
											submenu_name = :name,
											icon = :file_url, 
											update_by = :update_by
										WHERE
											id_submenu = :id_submenu");						
		if($updatStatus->execute([
				':name' =>  $dataComing["name"],
				':file_url' =>  $file_url,
				':id_submenu' => $dataComing["id_submenu"],
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['dataComing'] = [
				':name' =>  $dataComing["name"],
				':file_url' =>  $file_url,
				':id_submenu' => $dataComing["id_submenu"],
				':update_by' => $payload["username"]
			];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();	
		} 
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารทำรายการได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
		}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>