<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$imggggg = array();
	if(isset($dataComing["file_patch"]) && $dataComing["file_patch"] != null){
		$del_file="../../../../".$dataComing["file_patch"];
		$del=unlink($del_file);
	}else{
		$fethFile = $conmysql->prepare("SELECT
											file_patch
										FROM
											webcoopfiles
										WHERE id_gallery = :id_gallery ");
		$fethFile->execute([
					':id_gallery' => $dataComing["id_gallery"]
		]);
			
		while($imgPath = $fethFile->fetch(PDO::FETCH_ASSOC)){
					$del_file="../../../../".$imgPath["file_patch"];
					$del=unlink($del_file);
					$imggggg[] = $del_file;
		}
	}
	if($dataComing["type"]=="parent"){
		$del_img = $conmysql->prepare("DELETE FROM webcoopgallary WHERE id_gallery = :id_gallery");						
		if($del_img->execute([
				':id_gallery' =>  $dataComing["id_gallery"]
			])){
			$arrayResult["RESULT"] = TRUE;
			$arrayResult["delFile"] = $del;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}  	
	}else{
		$del_img = $conmysql->prepare("DELETE FROM webcoopfiles WHERE id_webcoopfile = :file_id");		
		if($del_img->execute([
				':file_id' =>  $dataComing["file_id"]
			])){
				$del_file="../../../../".$dataComing["file_patch"];
				$del=unlink($del_file);
				$arrayResult["RESULT"] = TRUE;
				$arrayResult["delFile"] = $del;
				echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
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