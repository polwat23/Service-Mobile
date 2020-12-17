<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','arr_id_editdata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$update_edit = $conmysql->prepare("UPDATE gcmembereditdata 
																SET is_updateoncore = '1'
																WHERE  id_editdata IN (".implode(',',$dataComing["arr_id_editdata"]).")");
		if($update_edit->execute()){
			$arrayStruc = [
				':menu_name' => "reportmembereditdata",
				':username' => $payload["username"],
				':use_list' => "update member edit data",
				':details' => implode(',',$dataComing["arr_id_editdata"])
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลื่ยนสถานะได้ กรุณาติดต่อผู้พัฒนา";
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