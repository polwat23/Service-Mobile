<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','send_date','id_sendahead'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead')){
		if(isset($dataComing["recuring"])){
			$updateSendAhead = $conmysql->prepare("UPDATE smssendahead SET send_date = :send_date WHERE id_sendahead = :id_sendahead");
			if($updateSendAhead->execute([
				':send_date' => date("Y-m-d H-i-s", strtotime($dataComing['send_date'])),
				':id_sendahead' => $dataComing["id_sendahead"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเวลาการส่งข้อความล่วงหน้าได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$updateSendAhead = $conmysql->prepare("UPDATE smssendahead SET send_date = :send_date WHERE id_sendahead = :id_sendahead");
			if($updateSendAhead->execute([
				':send_date' => date("Y-m-d H-i-s", strtotime($dataComing['send_date'])),
				':id_sendahead' => $dataComing["id_sendahead"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเวลาการส่งข้อความล่วงหน้าได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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