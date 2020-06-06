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
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1025",
				":error_desc" => "ไม่สามารถเปลี่ยนสถานะบัญชีธนาคารได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถเปลี่ยนสถานะบัญชีธนาคารได้เพราะ Update ลง gcbindaccount ไม่ได้"."\n"."Query => ".$updateAccountBeenbind->queryString."\n"."Param => ". json_encode([
				':bind_status' => $dataComing["bind_status"],
				':id_bindaccount' => $dataComing["id_bindaccount"]
			]);
			$lib->sendLineNotify($message_error);
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