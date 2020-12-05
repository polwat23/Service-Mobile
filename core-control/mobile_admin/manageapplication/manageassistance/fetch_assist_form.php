<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_welfare'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		$arrayWelfare = array();
		$fetchWelfare = $conmysql->prepare("SELECT 
										gfr.id_format_req_welfare,
										gfr.input_type,
										gfr.input_length,
										gfr.input_name,
										gfr.label_text,
										gfr.placeholder,
										gfr.default_value,
										gfr.input_format,
										gfr.is_use,
										gfr.is_required
									FROM gcformatreqwelfare gfr
									WHERE gfr.id_const_welfare = :id_welfare AND gfr.is_use <> '-9'");
		$fetchWelfare->execute([
			':id_welfare' => $dataComing["id_welfare"],
		]);
		while($dataWelfare = $fetchWelfare->fetch(PDO::FETCH_ASSOC)){
			$welfare = array();
			$welfare["INPUT_TYPE"] = $dataWelfare["input_type"];
			$welfare["INPUT_LENGTH"] = $dataWelfare["input_length"];
			$welfare["INPUT_NAME"] = $dataWelfare["input_name"];
			$welfare["LABEL_TEXT"] = $dataWelfare["label_text"];
			$welfare["PLACEHOLDER"] = $dataWelfare["placeholder"];
			$welfare["DEFAULT_VALUE"] = $dataWelfare["default_value"];
			$welfare["INPUT_FORMATE"] = $dataWelfare["input_format"];
			$welfare["IS_REQUIRED"] = $dataWelfare["is_required"];
			$welfare["IS_USE"] = $dataWelfare["is_use"];
			$welfare["ID_FORMAT_REQ_WELFARE"] = $dataWelfare["id_format_req_welfare"];
			$arrayWelfare[] = $welfare;
		}
		$arrayResult['FORM_DATA'] = $arrayWelfare;
		$arrayResult['ID'] = $dataComing["id_welfare"];
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>

