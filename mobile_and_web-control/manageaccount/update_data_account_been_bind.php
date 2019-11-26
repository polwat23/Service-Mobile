<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no','id_token'],$payload) && $lib->checkCompleteArgument(['menu_component','id_bindaccount','bind_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'ManagementAccount')){
		$updateAccountBeenbind = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = :bind_status WHERE id_bindaccount = :id_bindaccount");
		if($updateAccountBeenbind->execute([
			':bind_status' => $dataComing["bind_status"],
			':id_bindaccount' => $dataComing["id_bindaccount"],
		])){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "WS1023";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot update account status";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>