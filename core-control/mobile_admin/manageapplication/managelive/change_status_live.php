<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_live','status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managelive')){
	$updatemenu = $conmysql->prepare("UPDATE  gclive  SET is_use = :status
										  WHERE id_live=:id_live");
		if($updatemenu->execute([
			':status' => $dataComing["status"],
			':id_live' => $dataComing["id_live"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		echo json_encode($arrayResult);	
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