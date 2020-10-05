<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_board','f_name','l_name','position1','year','img'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageboard')){
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
				$createImage = $lib->base64_to_img($dataComing["img"],$file_name,$destination,null);
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}else{
					if($createImage){
						$img_url= $config["URL_SERVICE"]."resource/gallery_web_coop/board/".$createImage["normal_path"];
						$img_path = "resource/gallery_web_coop/board/".$createImage["normal_path"];
					
					
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
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
														f_name = :f_name,
														l_name = :l_name,
														position1 = :position1,
														position2 = :position2,
														year = :year,
														img_path = :img_path,
														img_url = :img_url,
														department = :department
													WHERE
														id_board = :id_board 
													");
			if($update_coop_board->execute([
				':f_name' =>  $dataComing["f_name"],
				':l_name' =>  $dataComing["l_name"],
				':img_path' => $img_path??null,
				':img_url' =>  $img_url??null,
				':position1' =>  $dataComing["position1"],
				':position2' =>  $dataComing["position2"],
				':year' =>  $dataComing["year"],
				':id_board' =>  $dataComing["id_board"],
				':department' =>  $dataComing["department"]
				
	
			])){
					$arrayResult['RESULT'] = True;
					echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา 333";
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