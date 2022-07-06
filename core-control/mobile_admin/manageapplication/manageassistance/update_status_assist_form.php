<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_format_req_welfare','is_use'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance',$conoracle)){
		$updateform = $conoracle->prepare("UPDATE gcformatreqwelfare SET is_use =:is_use WHERE id_format_req_welfare = :id_format_req_welfare");
		if($updateform->execute([
			':id_format_req_welfare' => $dataComing["id_format_req_welfare"],
			':is_use' => $dataComing["is_use"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเเก้ไขสถานะเเบบฟอร์มนี้ได้ กรุณาติดต่อผู้พัฒนา";
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