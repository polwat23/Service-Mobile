<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_quickmsg'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresquickmsg')){
			$updateText = $conmysql->prepare("UPDATE lbquickmessage SET is_use = '0', update_by = :update_by
													WHERE id_quickmsg = :id_quickmsg");
			if($updateText->execute([
				':update_by' => $payload["username"],
				':id_quickmsg' => $dataComing["id_quickmsg"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบข้อความได้ กรุณาติดต่อผู้พัฒนา";
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