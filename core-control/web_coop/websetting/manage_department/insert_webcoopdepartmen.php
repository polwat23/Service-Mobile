<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$inserGropuBalancdsheet = $conmysql->prepare("INSERT INTO webcoopdepartment(
										dept_name,
										create_by,
										update_by,
										type)
									VALUES(
										:dept_name,
										:create_by,
										:update_by,
										:type
									)");
	if($inserGropuBalancdsheet->execute([
		':dept_name' =>  $dataComing["dept_name"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"],
		':type' =>  $dataComing["type"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		$arrayResult['inserGropuBalancdsheet'] = $inserGropuBalancdsheet;
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
