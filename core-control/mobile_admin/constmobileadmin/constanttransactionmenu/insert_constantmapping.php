<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttransactionmenu')){
		$updateConstants = $conmysql->prepare("INSERT INTO gcmenuconstantmapping
		(menu_component, id_bankconstant)
		VALUES (:menu_component, :id_bankconstant)");
		if($updateConstants->execute([
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