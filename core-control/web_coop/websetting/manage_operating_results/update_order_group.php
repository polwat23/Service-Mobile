<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','group_data'],$dataComing)){
	$conmysql->beginTransaction();
	
	$index_order = 0;
	foreach($dataComing["group_data"] as $group_data){
		$index_order++;
		if(isset($group_data["ID_OPRESULTSGROUP"]) && $group_data["ID_OPRESULTSGROUP"] != null){
			$update_opresultsgroup = $conmysql->prepare("UPDATE webcoopopresultsgroup 
													SET group_order = :order,update_by = :update_by WHERE id_opresultsgroup = :id_opresultsgroup");
			if($update_opresultsgroup->execute([
				':order' => $index_order,
				':id_opresultsgroup' => $group_data["ID_OPRESULTSGROUP"],
				':update_by' => $payload["username"]
			])){
				continue;
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else if(isset($group_data["ID_OPRESULTSLIST"]) && $group_data["ID_OPRESULTSLIST"] != null){
			$update_opresultslist = $conmysql->prepare("UPDATE webcoopopresultslist 
												SET list_order = :order,update_by = :update_by
												WHERE id_opresultslist = :id_opresultsgroup");
			if($update_opresultslist->execute([
				':order' => $index_order,
				':id_opresultsgroup' => $group_data["ID_OPRESULTSLIST"],
				':update_by' => $payload["username"]
			])){
				continue;
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
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