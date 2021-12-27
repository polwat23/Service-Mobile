<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_const_welfare','input_type','input_name','label_text'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		
		$id_format_req_welfare  = $func->getMaxTable('id_format_req_welfare' , 'gcformatreqwelfare');	
		$updaeusername = $conoracle->prepare("INSERT INTO gcformatreqwelfare (
										id_format_req_welfare,
										id_const_welfare, 
										input_type, 
										input_length, 
										input_name, 
										label_text, 
										placeholder, 
										default_value, 
										input_format,
										is_required) 
										VALUES (:id_format_req_welfare,
										:id_const_welfare,
										:input_type,
										:input_length,
										:input_name,
										:label_text,
										:placeholder,
										:default_value,
										:input_format,
										:is_required)");
		if($updaeusername->execute([
			':id_format_req_welfare' => $id_format_req_welfare,
			':id_const_welfare' => $dataComing["id_const_welfare"],
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
				':use_list' => "insert assistform",
				':details' => "add row ".$dataComing["input_name"]." (".$dataComing["id_const_welfare"].")"
			];
			
			$log->writeLog('manageapplication',$arrayStruc);	

			$arrayResult["RESULT"] = TRUE;
			$arrayResult["INPUT_LENGTH"] = $dataComing["input_length_spc_"];
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มเเบบฟอร์มนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			$arrayResult["dataComing"] = [
				':id_const_welfare' => $dataComing["id_const_welfare"],
				':input_type' => $dataComing["input_type"],
				':input_length' => $dataComing["input_length_spc_"],
				':input_name' => $dataComing["input_name"],
				':label_text' => $dataComing["label_text"],
				':placeholder' => $dataComing["placeholder"],
				':default_value' => $dataComing["default_value"],
				':input_format' => $dataComing["input_format"] ? json_encode($dataComing["input_format"], JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT ) : null,
				':is_required' => $dataComing["is_required"]
			];
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