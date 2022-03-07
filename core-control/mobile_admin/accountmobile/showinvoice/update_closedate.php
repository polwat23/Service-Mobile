<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','dateclose_end'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','showinvoice')){
		$update_tel = $conmysql->prepare("UPDATE gcendclosedate 
																SET end_date = :dateclose_end");
		if($update_tel->execute([
			':dateclose_end' => $dataComing["dateclose_end"]
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
			$arrayResult['RESPONSE'] = "ไม่สามารถเเสดงผลได้ กรุณาติดต่อผู้พัฒนา";
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















