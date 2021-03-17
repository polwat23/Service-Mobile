<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_gallery'],$dataComing)){
	
	$del_partner = $conmysql->prepare("DELETE FROM webcoopgallary WHERE id_gallery = :id_gallery");						
	if($del_partner->execute([
			':id_gallery' =>  $dataComing["id_gallery"]
		])){
		$imgPath = $dataComing["imgPath"];
		$del_file="../../../../".$imgPath;
		unlink($del_file);
		$arrayResult["RESULT"] = TRUE;
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