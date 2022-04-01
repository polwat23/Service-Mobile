<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','doc_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','uploaddocuments')){
		$update_email = $conmysql->prepare("UPDATE gcdocuploadfile 
																SET doc_status = '0'
																WHERE  doc_no = :doc_no");
		if($update_email->execute([
			':doc_no' => $dataComing["doc_no"]
		])){
			$arrayStruc = [
				':menu_name' => "manageuser",
				':username' => $payload["username"],
				':use_list' => "change Tel",
				':details' => $dataComing["old_tel"].' , '.$dataComing["new_tel"]
			];
			
			//$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่เปลื่อยนอีเมลได้ กรุณาติดต่อผู้พัฒนา";
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















