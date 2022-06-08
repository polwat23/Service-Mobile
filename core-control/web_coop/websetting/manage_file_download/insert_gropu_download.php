<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$inserGropudownload = $conmysql->prepare("INSERT INTO webcoopgroupdownload(
										name,
										create_by,
										update_by)
									VALUES(
										:name,
										:create_by,
										:update_by
									)");
	if($inserGropudownload->execute([
		':name' =>  $dataComing["name"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);

	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['RESULT'] = FALSE;
		$arrayResult['insertGropudownload'] = $insertGropudownload;
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

