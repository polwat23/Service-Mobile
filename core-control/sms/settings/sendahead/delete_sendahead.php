<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_sendahead'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead')){
		$deleteSendAhead = $conmysql->prepare("DELETE FROM smssendahead WHERE id_sendahead = :id_sendahead");
		if($deleteSendAhead->execute([
			':id_sendahead' => $dataComing["id_sendahead"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบการส่งข้อความล่วงหน้าได้ กรุณาติดต่อผู้พัฒนา";
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