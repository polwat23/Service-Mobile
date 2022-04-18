<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','cremationlist')){
	
		$insertCremationList = $conmysql->prepare("UPDATE gccremationlist SET is_use = '0' WHERE cremation_id = :cremation_id");
		if($insertCremationList->execute([
			':cremation_id' => $dataComing["id"]
		])){
			$arrayStruc = [
				':menu_name' => "cremationlist",
				':username' => $payload["username"],
				':use_list' =>"delete cremation",
				':details' => "cremation_id => ".$dataComing["id"]
			];
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ลบข้อมูลฌาปนกิจไม่สำเร็จ";
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