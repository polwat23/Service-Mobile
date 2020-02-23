<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_history'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Notification')){
		$readHistory = $conmysql->prepare("UPDATE gchistory SET his_read_status = '1' WHERE member_no = :member_no and id_history = :id_history");
		$readHistory->execute([
			':member_no' => $payload["member_no"],
			':id_history' => $dataComing["id_history"]
		]);
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>