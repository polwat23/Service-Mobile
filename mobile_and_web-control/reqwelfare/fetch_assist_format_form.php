<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_const_welfare'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$arrayGrpForm = array();
		$year = date("Y");
		$getFormatForm = $conmysql->prepare("SELECT input_type,input_length,input_name,label_text,placeholder,default_value,is_required,input_format
											FROM gcformatreqwelfare 
											WHERE id_const_welfare = :id_const_welfare and is_use = '1'");
		$getFormatForm->execute([
			':id_const_welfare' => $dataComing["id_const_welfare"]
		]);
		while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
			$arrayForm = array();
			$arrayForm["INPUT_TYPE"] = $rowForm["input_type"];
			$arrayForm["INPUT_LENGTH"] = $rowForm["input_length"];
			$arrayForm["INPUT_NAME"] = $rowForm["input_name"];
			$arrayForm["LABEL_TEXT"] = $rowForm["label_text"];
			$arrayForm["PLACEHOLDER"] = $rowForm["placeholder"];
			$arrayForm["DEFAULT_VALUE"] = $rowForm["default_value"];
			$arrayForm["INPUT_FORMAT"] = $rowForm["input_format"];
			$arrayForm["IS_REQUIRED"] = $rowForm["is_required"];
			$arrayGrpForm[] = $arrayForm;
		}
		$arrayResult['FORM_ASSIST'] = $arrayGrpForm;
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