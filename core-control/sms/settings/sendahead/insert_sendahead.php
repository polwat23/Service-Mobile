<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','send_message','destination','send_date'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead',$conmysql)){
		$insertSendAhead = $conmysql->prepare("INSERT INTO smssendahead(send_message,destination,send_date,create_by)
												VALUES(:send_message,:destination,:send_date,:username)");
		if($insertSendAhead->execute([
			':send_message' => $dataComing["send_message"],
			':destination' => $dataComing["destination"],
			':send_date' => $dataComing["send_date"],
			':username' => $payload["username"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "insert";
			$arrayResult['RESPONSE'] = "Cannot insert send ahead";
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