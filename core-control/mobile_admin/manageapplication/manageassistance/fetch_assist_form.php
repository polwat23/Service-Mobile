<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_welfare'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		$arrayWelfare = array();
		$fetchWelfare = $conoracle->prepare("SELECT 
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
			$welfare["INPUT_TYPE"] = $dataWelfare["INPUT_TYPE"];
			$welfare["INPUT_LENGTH"] = $dataWelfare["INPUT_LENGTH"];
			$welfare["INPUT_NAME"] = $dataWelfare["INPUT_NAME"];
			$welfare["LABEL_TEXT"] = $dataWelfare["LABEL_TEXT"];
			$welfare["PLACEHOLDER"] = $dataWelfare["PLACEHOLDER"];
			$welfare["DEFAULT_VALUE"] = $dataWelfare["default_value"];
			$welfare["INPUT_FORMATE"] = $dataWelfare["INPUT_FORMAT"];
			$welfare["IS_REQUIRED"] = $dataWelfare["IS_REQUIRED"];
			$welfare["IS_USE"] = $dataWelfare["IS_USE"];
			$welfare["ID_FORMAT_REQ_WELFARE"] = $dataWelfare["ID_FORMAT_REQ_WELFARE"];
			$arrayWelfare[] = $welfare;
		}
		$arrayResult['FORM_DATA'] = $arrayWelfare;
		$arrayResult['ID'] = $dataComing["id_welfare"];
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>

