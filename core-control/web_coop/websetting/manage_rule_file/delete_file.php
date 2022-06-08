<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_gallery'],$dataComing)){
	
	$del_img = $conmysql->prepare("DELETE FROM webcoopgallary WHERE id_gallery = :id_gallery");						
	if($del_img->execute([
			':id_gallery' =>  $dataComing["id_gallery"]
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

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>