<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_token','type_logout'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','userlogin')){
		if($func->logout($dataComing['id_token'],$dataComing['type_logout'])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);	
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถบังคับผู้ใช้นี้ออกจากระบบได้ กุรณาติดต่อผู้พัฒนา";
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