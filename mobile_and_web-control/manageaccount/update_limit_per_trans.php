<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','sigma_key','limit_amt'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$updateLimitTrans = $conmysql->prepare("UPDATE gcbindaccount SET limit_amt = :limit_amt WHERE sigma_key = :sigma_key");
		if($updateLimitTrans->execute([
			':limit_amt' => $dataComing["limit_amt"],
			':sigma_key' => $dataComing["sigma_key"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':limit_amt' => $dataComing["limit_amt"],
				':sigma_key' => $dataComing["sigma_key"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateLimitTrans;
			$arrError["ERROR_CODE"] = 'WS1026';
			$lib->addLogtoTxt($arrError,'changelimit_error');
			$arrayResult['RESPONSE_CODE'] = "WS1026";
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