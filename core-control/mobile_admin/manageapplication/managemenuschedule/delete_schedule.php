<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_menuschedule'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenuschedule')){
		
		$update_schedule = $conmysql->prepare("UPDATE gcmenuschedule SET is_use = '0' WHERE id_menuschedule = :id_menuschedule");
		if($update_schedule->execute([
			':id_menuschedule' => $dataComing["id_menuschedule"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ลบกำหนดการไม่สำเร็จ";
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