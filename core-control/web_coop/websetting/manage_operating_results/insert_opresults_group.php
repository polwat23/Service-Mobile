<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','group_name'],$dataComing)){
	
	$insert_group = $conmysql->prepare("INSERT INTO webcoopopresultsgroup (group_name,create_by,update_by) 
										VALUES (:group_name,:create_by,:update_by)");
	if($insert_group->execute([
		':group_name' =>  $dataComing["group_name"],
		':create_by' => $payload["username"],
		':update_by' => $payload["username"]
	])){
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);

	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มกลุ่มได้ กรุณาติดต่อผู้พัฒนา ";
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