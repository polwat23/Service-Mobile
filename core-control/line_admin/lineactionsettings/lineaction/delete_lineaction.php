<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_action'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineactions')){
			$updateText = $conmysql->prepare("UPDATE lbaction SET is_use = '0', update_by = :update_by
													WHERE id_action = :id_action");
			if($updateText->execute([
				':update_by' => $payload["username"],
				':id_action' => $dataComing["id_action"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อความได้ กรุณาติดต่อผู้พัฒนา";
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