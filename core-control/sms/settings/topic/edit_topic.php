<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_template','topic_name','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic',$conoracle) && is_numeric($dataComing["id_template"])){
		$conoracle->beginTransaction();
		$UpdateMenuSMS = $conoracle->prepare("UPDATE coresubmenu SET menu_name = :topic_name WHERE id_submenu = :id_submenu");
		if($UpdateMenuSMS->execute([
			':topic_name' => $dataComing["topic_name"],
			':id_submenu'=> $dataComing["id_submenu"]
		])){
			$updateMatching = $conoracle->prepare("UPDATE smstopicmatchtemplate SET id_smstemplate = :id_template WHERE id_submenu = :id_submenu");
			if($updateMatching->execute([
				':id_template' => $dataComing["id_template"],
				':id_submenu'=> $dataComing["id_submenu"]
			])){
				$conoracle->commit();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$conoracle->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$conoracle->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
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