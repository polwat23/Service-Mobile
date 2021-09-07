<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_apprwd_constant'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
			$updateConstants = $conmssql->prepare("UPDATE gcconstantapprwithdrawal SET is_use = '0' WHERE id_apprwd_constant = :id_apprwd_constant");
			if($updateConstants->execute([
				'id_apprwd_constant'  => $dataComing["id_apprwd_constant"]
			])){
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