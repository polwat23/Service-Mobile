<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_task'],$dataComing)){
	
	$deleteSendAhead = $conmysql->prepare("UPDATE webcoopgctaskevent SET is_use = '-9' WHERE id_task = :id_task");
	if($deleteSendAhead->execute([
		':id_task' => $dataComing["id_task"]
	])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถลบกิจกรรมนี้ได้ กรุณาติดต่อผู้พัฒนา";
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