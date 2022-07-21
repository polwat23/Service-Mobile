<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount',$conoracle)){
		$updateConstants = $conoracle->prepare("UPDATE gcbankconstant
		SET is_use = '0'
		WHERE id_bankconstant = :id_bankconstant");
		if($updateConstants->execute([
			':id_bankconstant' => $dataComing["id_bankconstant"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"delete gcbankconstant",
					':details' => "id_bankconstant => ".$dataComing["id_bankconstant"]
			];
			$log->writeLog('manageuser',$arrayStruc, false, $conoracle);
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