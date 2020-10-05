<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageboard')){	
		$pathImg = null;
		$urlImg = null;
		if(isset($dataComing["img"]) && $dataComing["img"] != null){
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/officer';
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
					$pathImg = "resource/gallery_web_coop/officer/".$createImage["normal_path"];
					$urlImg = $config["URL_SERVICE"]."resource/gallery_web_coop/officer/".$createImage["normal_path"];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
		
		if($dataComing["type"]=='0'){
			$insert_board = $conmysql->prepare("INSERT INTO webcoopboardofdirectors(
											f_name,
											l_name,
											position1,
											position2,
											year,
											img_path,
											img_url,
											emp_type,
											department
											)
										VALUES(
											:f_name,
											:l_name,
											:position1,
											:position2,
											:year,
											:img_path,
											:img_url,
											:emp_type,
											:department
										)");
			if($insert_board->execute([
				':f_name' =>  $dataComing["f_name"],
				':l_name' =>  $dataComing["l_name"],
				':position1' =>  $dataComing["position1"],
				':position2' =>  $dataComing["position2"],
				':year' =>  $dataComing["year"],
				':emp_type' => '1',
				':department' =>  $dataComing["department"]??null,
				':img_path' => $pathImg ?? null,
				':img_url' => $urlImg ?? null
			])){	
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
									
			}else{
				$del_file="../../../../".$pathImg;
				$del=unlink($del_file);
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา3333 ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			} 
			
		}else{
			$insert_board = $conmysql->prepare("INSERT INTO webcoopboardofdirectors(
											year,
											img_path,
											img_url,
											type,
											emp_type
											)
										VALUES(
											:year,
											:img_path,
											:img_url,
											:type,
											:emp_type
											
										)");
			if($insert_board->execute([
				':year' =>  $dataComing["year"],
				':img_path' => $pathImg ?? null,
				':img_url' => $urlImg ?? null,
				':type' => $dataComing["type"] ?? null,
				':emp_type' => '1'
				
			])){	
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
									
			}else{
				$del_file="../../../../".$pathImg;
				$del=unlink($del_file);
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			} 
			
			
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
