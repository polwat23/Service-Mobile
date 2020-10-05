<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_opresultsgroup'],$dataComing)){
	$conmysql->beginTransaction();
	
	$update_opresultsgroup = $conmysql->prepare("UPDATE webcoopopresultslist SET is_use = '0', update_by = :update_by
								WHERE id_opresultsgroup = :id_opresultsgroup");
	if($update_opresultsgroup->execute([
		':id_opresultsgroup' => $dataComing["id_opresultsgroup"],
		':update_by' => $payload["username"]
	])){
		
	}else{
		$conmysql->rollback();
		$arrayResult['RESPONSE'] = "ไม่สามารถลบได้ กรุณาติดต่อผู้พัฒนา";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
	
	$update_opresultslist = $conmysql->prepare("UPDATE webcoopopresultslist 
											SET parent_id = '0',list_order = null,update_by = :update_by
											WHERE parent_id = :parent_id");
	if($update_opresultslist->execute([
		':parent_id' => $dataComing["id_opresultsgroup"],
		':update_by' => $payload["username"]
	])){
		
	}else{
		$conmysql->rollback();
		$arrayResult['RESPONSE'] = "ไม่สามารถลบได้ กรุณาติดต่อผู้พัฒนา";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
	
	
	$conmysql->commit();
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);	
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>