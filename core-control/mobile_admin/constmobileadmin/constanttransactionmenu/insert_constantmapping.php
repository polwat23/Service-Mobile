<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttransactionmenu',$conoracle)){
		$id_constantmapping = $func->getMaxTable('id_constantmapping' , 'gcmenuconstantmapping',$conoracle);
		$updateConstants = $conoracle->prepare("INSERT INTO gcmenuconstantmapping
		(id_constantmapping ,menu_component, id_bankconstant)
		VALUES (:id_constantmapping, :menu_component, :id_bankconstant)");
		if($updateConstants->execute([
			':id_constantmapping' => $id_constantmapping,
			':menu_component' => $dataComing["menu_component"],
			':id_bankconstant' => $dataComing["id_bankconstant"]
		])){
			$arrayStruc = [
					':menu_name' => "constanttransactionmenu",
					':username' => $payload["username"],
					':use_list' =>"insert gcmenuconstantmapping",
					':details' => "menu_component => ".$dataComing["menu_component"].
								" id_bankconstant => ".$dataComing["id_bankconstant"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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