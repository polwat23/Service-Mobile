<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_const_welfare'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$arrayGrpForm = array();
		$getFormatForm = $conmysql->prepare("SELECT input_type,input_length,input_name,label_text,placeholder,default_value
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>