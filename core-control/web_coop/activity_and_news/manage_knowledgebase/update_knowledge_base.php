<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','id_knowledge'],$dataComing)){
	if($dataComing["type"] == 'is_use'){
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopknowledgebase
										SET
											is_use = :is_use,
											update_by = :update_by
										WHERE
											id_knowledge = :id_knowledge");						
		if($updatStatus->execute([
				':is_use' =>  '-9',
				':id_knowledge' => $dataComing["id_knowledge"],
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
		$fileUrl = null;
		$imgUrl = null;
		$file = $dataComing["file"];

		$img = $dataComing["img"];
	
		
		if(isset($dataComing["file"]) && $dataComing["file"] != null){
			if($file["status"] == "old"){
				$groupfile = $file["file"];
				
				$fileUrl = $groupfile["url"];
			}else{
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/';
				$file_name = $file["name"];
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_pdf($file["file"],$file_name,$destination,null);
				if($createImage){
					$fileUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['file'] = $file;
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		
		if(isset($dataComing["img"]) && $dataComing["img"] != null){
			
			if($img["status"] == "old"){
				$imgUrl = $img["url"]; 
			}else{
				$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/'.$forderAcc;
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
						$imgUrl = $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$forderAcc."/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}
		
		}
		
		$detail_html = '<!DOCTYPE HTML>
							<html>
							<head>
						  <meta charset="UTF-8">
						  <meta name="viewport" content="width=device-width, initial-scale=1.0">
						  '.$dataComing["html_root_"].'
						  </body>
							</html>';
		
	
		$updatStatus = $conmysql->prepare("
										UPDATE
											webcoopknowledgebase
										SET
											submenuknowledge_id = :submenuknowledge_id,
											title = :title, 
											detail = :detail,
											img_url = :img_url,
											file_url = :file_url,
											update_by = :update_by
										WHERE
											id_knowledge = :id_knowledge");						
		if($updatStatus->execute([
				':submenuknowledge_id' =>  $dataComing["submenuknowledge_id"],
				':title' =>  $dataComing["title"],
				':id_knowledge' => $dataComing["id_knowledge"],
				':detail' => $detail_html,
				':img_url' => $imgUrl,
				':file_url' => $fileUrl,
				':update_by' => $payload["username"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['dataComing'] = [
				':submenuknowledge_id' =>  $dataComing["submenuknowledge_id"],
				':title' =>  $dataComing["title"],
				':id_knowledge' => $dataComing["id_knowledge"],
				':detail' => $detail_html,
				':img_url' => $imgUrl,
				':file_url' => $fileUrl,
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