<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$inserGropuBalancdsheet = $conmysql->prepare("INSERT INTO webcooposition(
                                        post_name,
										create_by,
                                        level,
										type,
										update_by)
									VALUES(
                                        :post_name,
										:create_by,
                                        :level,
										:type,
										:update_by
									)");
	if($inserGropuBalancdsheet->execute([
		':post_name' =>  $dataComing["post_name"],
        ':level' =>  $dataComing["level"],
        ':type' =>  $dataComing["type"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
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
