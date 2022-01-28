<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_editdata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		
		$update_edit = $conmysql->prepare("UPDATE gcmembereditdata SET is_updateoncore = '-1'
										  WHERE  id_editdata = :id_editdata");
		if($update_edit->execute([
				':id_editdata' => $dataComing["id_editdata"]
			])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}		
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