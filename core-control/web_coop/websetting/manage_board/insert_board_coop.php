<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$pathImg = null;
	$urlImg = null;
	if(isset($dataComing["img"]) && $dataComing["img"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/board';
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($dataComing["img"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$pathImg = "resource/gallery_web_coop/board/".$createImage["normal_path"];
				$urlImg = $config["URL_SERVICE"]."resource/gallery_web_coop/board/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
	
	if($dataComing["type"]=='0'){
		$insert_board = $conmysql->prepare("INSERT INTO webcoopboardofdirectors(
										fullname,
										position1,
										position2,
										year,
										img_path,
										img_url,
										department_id
										)
									VALUES(
										:fullname,
										:position1,
										:position2,
										:year,
										:img_path,
										:img_url,
										:department
									)");
		if($insert_board->execute([
			':fullname' =>  $dataComing["fullname"],
			':position1' =>  $dataComing["position1"],
			':position2' =>  $dataComing["position2"]??NULL,
			':year' =>  $dataComing["year"],
			':img_path' => $pathImg ?? null,
			':img_url' => $urlImg ?? null,
			':department' =>  $dataComing["department"]??null
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
		
	}else{
		$insert_board = $conmysql->prepare("INSERT INTO webcoopboardofdirectors(
										year,
										img_path,
										img_url,
										type
										)
									VALUES(
										:year,
										:img_path,
										:img_url,
										:type
									)");
		if($insert_board->execute([
			':year' =>  $dataComing["year"],
			':img_path' => $pathImg ?? null,
			':img_url' => $urlImg ?? null,
			':type' => $dataComing["type"] ?? null
		])){	
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
								
		}else{
			$del_file="../../../../".$pathImg;
			$del=unlink($del_file);

			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['pathImg'] = $pathImg;
			$arrayResult['urlImg'] = $urlImg;
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
