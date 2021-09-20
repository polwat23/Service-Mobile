<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_quickmsg','id_action'],$dataComing)){
	if($func->check_permission_core($payload,'line','lineresquickmsg')){
		if(isset($dataComing["id_action"]) || $dataComing["id_action"] != ''){
			$updateText = $conmysql->prepare("UPDATE lbquickmessage SET id_action = :id_action, update_by = :update_by
													WHERE id_quickmsg = :id_quickmsg");
			if($updateText->execute([
				':id_action' => $dataComing["id_action"],
				':update_by' => $payload["username"],
				':id_quickmsg' => $dataComing["id_quickmsg"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อความได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเป็นค่าว่างได้";
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