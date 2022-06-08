<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_board','position1','year'],$dataComing)){
	
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
		
			$destination = __DIR__.'/../../../../resource/gallery_web_coop/board/';
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
					$img_url= $config["URL_SERVICE"]."resource/gallery_web_coop/board/".$createImage["normal_path"];
					$img_path = "resource/gallery_web_coop/board/".$createImage["normal_path"];
				
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
					$arrayResult['Img'] = $dataComing["img"];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
			
		}
	}

	$update_coop_board = $conmysql->prepare("UPDATE
													webcoopboardofdirectors
												SET
													    fullname = :fullname,
														position1 = :position1,
														position2 = :position2,
														YEAR = :year,
														img_path = :img_path,
														img_url = :img_url
												WHERE
													id_board = :id_board 
												");
	if($update_coop_board->execute([
			':fullname' => $dataComing["fullname"],
			':position1' =>  $dataComing["position1"],
			':position2' =>  $dataComing["position2"]??NULL,
			':year' =>  $dataComing["year"],
			':img_path' => $img_path??null,
			':img_url' =>  $img_url??null,
			':id_board' =>  $dataComing["id_board"]
		
	])){
		$arrayResult['RESULT'] = True;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['DATA'] = [
			':fullname' => $dataComing["fullname"],
			':position1' =>  $dataComing["position1"],
			':position2' =>  $dataComing["position2"],
			':year' =>  $dataComing["year"],
			':img_path' => $img_path??null,
			':img_url' =>  $img_url??null,
			':id_board' =>  $dataComing["id_board"]
	];
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