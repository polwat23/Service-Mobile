<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_conttranqr'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantqrcode')){
		
		$updateConstants = $conmysql->prepare("UPDATE gcconttypetransqrcode SET is_use = '0' WHERE id_conttranqr = :id_conttranqr");
		if($updateConstants->execute([
			':id_conttranqr' => $dataComing["id_conttranqr"]
		])){
			$arrayStruc = [
					':menu_name' => "constantqrcode",
					':username' => $payload["username"],
					':use_list' =>"delete GCCONTTYPETRANSQRCODE",
					':details' => "id_conttranqr => ".$dataComing["id_conttranqr"]
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