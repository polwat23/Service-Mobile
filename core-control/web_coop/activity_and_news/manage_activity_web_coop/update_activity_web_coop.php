	<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$deleteGallery = $conmysql->prepare("UPDATE webcoopactivity SET 
												activity_title = :activity_title,
												activity_detail = :activity_detail
											WHERE webcoopactivity_id = :activity_id");
	if($deleteGallery->execute([
		':activity_title' =>  $dataComing["activity_title"],
		':activity_id' =>  $dataComing["activity_id"],
		':activity_detail' =>  $dataComing["activity_detail"]
	])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามาแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา  ";
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