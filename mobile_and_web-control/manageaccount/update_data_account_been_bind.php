<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_bindaccount','bind_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$updateAccountBeenbind = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = :bind_status WHERE id_bindaccount = :id_bindaccount");
		if($updateAccountBeenbind->execute([
			':bind_status' => $dataComing["bind_status"],
			':id_bindaccount' => $dataComing["id_bindaccount"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':bind_status' => $dataComing["bind_status"],
				':id_bindaccount' => $dataComing["id_bindaccount"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateAccountBeenbind;
			$arrError["ERROR_CODE"] = 'WS1025';
			$lib->addLogtoTxt($arrError,'manageaccount_error');
			$arrayResult['RESPONSE_CODE'] = "WS1025";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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