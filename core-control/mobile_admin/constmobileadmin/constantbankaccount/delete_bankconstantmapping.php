<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount')){
		$updateConstants = $conmysql->prepare("UPDATE gcbankconstantmapping
		SET is_use = '0'
		WHERE id_bankconstantmapping = :id_bankconstantmapping");
		if($updateConstants->execute([
			':id_bankconstantmapping' => $dataComing["id_bankconstantmapping"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"delete gcbankconstantmapping",
					':details' => "id_bankconstantmapping => ".$dataComing["id_bankconstantmapping"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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