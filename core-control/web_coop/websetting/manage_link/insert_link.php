<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$insert_file = $conmysql->prepare("INSERT INTO webcooplink(
										name,
										link,  
										create_by,
										update_by)
									VALUES(
										:name,
										:link,
										:create_by,
										:update_by
									)");
	if($insert_file->execute([
		':name' =>  $dataComing["name"],
		':link' =>  $dataComing["link"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
		
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
							
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['dataComing'] = $dataComing;
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