<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','parent_id','id_opresultslist'],$dataComing)){
	
	$update_opresultsgroup = $conmysql->prepare("UPDATE webcoopopresultslist 
											SET parent_id = :parent_id,list_order = null,update_by = :update_by
											WHERE id_opresultslist = :id_opresultslist");
	if($update_opresultsgroup->execute([
		':parent_id' => $dataComing["parent_id"],
		':id_opresultslist' => $dataComing["id_opresultslist"],
		':update_by' => $payload["username"]
	])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
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