<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAssistGrp = array();
		$fetchAssistType = $conmysql->prepare("SELECT id_const_welfare,welfare_type_desc,welfare_type_code FROM gcconstantwelfare WHERE is_use = '1'");
		$fetchAssistType->execute();
		while($rowAssistType = $fetchAssistType->fetch(PDO::FETCH_ASSOC)){
			$arrAssist = array();
			$arrAssist["ID_CONST_WELFARE"] = $rowAssistType["id_const_welfare"];
			$arrAssist["WELFARE_DESC"] = $rowAssistType["welfare_type_desc"];
			$arrAssist["ASSISTTYPE_CODE"] = $rowAssistType["welfare_type_code"];
			$arrAssistGrp[] = $arrAssist;
		}
		$arrayResult['WELFARE_TYPE'] = $arrAssistGrp;
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
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>