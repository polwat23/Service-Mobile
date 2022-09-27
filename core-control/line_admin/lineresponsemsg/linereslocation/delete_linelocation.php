<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_location'],$dataComing)){
	if($func->check_permission_core($payload,'line','linereslocationmsg')){
			$updateText = $conmysql->prepare("UPDATE lblocation SET is_use = '0', update_by = :update_by
													WHERE id_location = :id_location");
			if($updateText->execute([
				':update_by' => $payload["username"],
				':id_location' => $dataComing["id_location"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบตำแหน่งนี้ได้ กรุณาติดต่อผู้พัฒนา";
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