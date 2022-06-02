<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_format_req_welfare','input_type','input_name','label_text'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		$updateform = $conoracle->prepare("UPDATE gcformatreqwelfare 
									SET input_type = :input_type,
									input_length = :input_length,
									input_name = :input_name,
									label_text = :label_text,
									placeholder = :placeholder,
									default_value = :default_value,
									input_format = :input_format,
									is_required = :is_required
									WHERE id_format_req_welfare = :id_format_req_welfare");
		if($updateform->execute([
			':id_format_req_welfare' => $dataComing["id_format_req_welfare"],
			':input_type' => $dataComing["input_type"],
			':input_length' => $dataComing["input_length_spc_"],
			':input_name' => $dataComing["input_name"],
			':label_text' => $dataComing["label_text"],
			':placeholder' => $dataComing["placeholder"],
			':default_value' => $dataComing["default_value"],
			':input_format' => $dataComing["input_format"] ? json_encode($dataComing["input_format"], JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT ) : null,
			':is_required' => $dataComing["is_required"]
		])){
			
			$arrayStruc = [
				':menu_name' => "manageassistance",
				':username' => $payload["username"],
				':use_list' => "update assistform",
				':details' => "id_format_req_welfare : ".$dataComing["id_format_req_welfare"].":input_type=>".$dataComing["input_type"].":input_length_spc_=>".$dataComing["input_length_spc_"].":input_name=>".$dataComing["input_name"].":label_text=>".$dataComing["label_text"].":placeholder=>".$dataComing["placeholder"].
				":default_value=>".$dataComing["default_value"].":input_format=>".($dataComing["input_format"] ? json_encode($dataComing["input_format"], JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT ) : "null").":is_required=>".$dataComing["is_required"]
			];
			
			$log->writeLog('manageapplication',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			$arrayResult["INPUT_LENGTH"] = $dataComing["input_length_spc_"];
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเเก้ไขเเบบฟอร์มนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
			
		}
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