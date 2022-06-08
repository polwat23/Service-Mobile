<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$fethImg = $conmysql->prepare("SELECT
										file_patch
									FROM
										webcoopfiles
									WHERE id_gallery = :id_gallery AND file_url = :img");
	$fethImg->execute([
			':id_gallery' => $dataComing["id_gallery"],
			':img' =>  $dataComing["img"]
	]);
	$imgPath = $fethImg->fetch(PDO::FETCH_ASSOC);
	$del_file="../../../../".$imgPath["file_patch"];;
	$delFile= unlink($del_file);
	

	$del_img = $conmysql->prepare("DELETE FROM webcoopfiles WHERE file_url = :img AND id_gallery = :id_gallery");						
	if($del_img->execute([
			':id_gallery' =>  $dataComing["id_gallery"],
			':img' =>  $dataComing["img"]
		])){
		$arrayResult["RESULT"] = TRUE;
		$arrayResult["delFile"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
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