<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id'],$dataComing)){
	
	$del_img = $conmysql->prepare("UPDATE webcoopmeetingagenda SET is_use ='-9' WHERE id_meettingagenda = :id");						
	if($del_img->execute([
			':id' =>  $dataComing["id"]
		])){
			$del_file="../../../../".$dataComing["file_patch"];
			$del=unlink($del_file);
			$arrayResult["RESULT"] = TRUE;
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