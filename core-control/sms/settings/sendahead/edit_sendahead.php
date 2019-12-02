<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','send_message','destination','send_date','id_sendahead'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead',$conmysql)){
		$updateSendAhead = $conmysql->prepare("UPDATE smssendahead SET send_message = :send_message,destination = :destination,
												send_date = :send_date WHERE id_sendahead = :id_sendahead");
		if($updateSendAhead->execute([
			':send_message' => $dataComing["send_message"],
			':destination' => $dataComing["destination"],
			':send_date' => $lib->convertdate($dataComing["send_message"],'y-n-d'),
			':id_sendahead' => $dataComing["id_sendahead"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "update";
			$arrayResult['RESPONSE'] = "Cannot edit send ahead";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>