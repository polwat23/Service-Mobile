<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttransactionmenu',$conoracle)){
		$updateConstants = $conoracle->prepare("UPDATE gcmenuconstantmapping
		SET is_use = '0'
		WHERE id_constantmapping = :id_constantmapping");
		if($updateConstants->execute([
			':id_constantmapping' => $dataComing["id_constantmapping"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"delete gcmenuconstantmapping",
					':details' => "id_constantmapping => ".$dataComing["id_constantmapping"]
			];
			$log->writeLog('manageuser',$arrayStruc,false,$conoracle);
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