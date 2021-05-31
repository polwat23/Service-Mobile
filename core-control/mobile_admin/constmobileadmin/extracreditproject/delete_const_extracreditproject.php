<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_extra_credit'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extracreditproject')){
		$updateConstants = $conmysql->prepare("UPDATE gcconstantextracreditproject SET is_use = '0', update_by = :update_by WHERE id_extra_credit = :id_extra_credit");
		if($updateConstants->execute([
			':update_by' => $payload["username"],
			':id_extra_credit' => $dataComing["id_extra_credit"],
		])){
			$arrayStruc = [
				':menu_name' => "extracreditproject",
				':username' => $payload["username"],
				':use_list' =>"delete gcconstantextracreditproject",
				':details' => "id_extra_credit = ".$dataComing["id_extra_credit"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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