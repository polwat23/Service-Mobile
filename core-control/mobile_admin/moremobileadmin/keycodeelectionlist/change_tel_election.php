<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_regelection','tel_mobile','old_tel'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','keycodeelectionlist')){
			$approveReqLoan = $conmysql->prepare("UPDATE logregisterelection SET tel_mobile = :tel_mobile WHERE id_regelection = :id_regelection");
			if($approveReqLoan->execute([
				':tel_mobile' => $dataComing["tel_mobile"],
				':id_regelection' => $dataComing["id_regelection"]
			])){
				$arrayStruc = [
					':menu_name' => "keycodeelectionlist",
					':username' => $payload["username"],
					':use_list' => "change Tel",
					':details' => $dataComing["old_tel"].' , '.$dataComing["tel_mobile"]
				];
				
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการนี้ได้ กรุณาติดต่อผู้พัฒนา";
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