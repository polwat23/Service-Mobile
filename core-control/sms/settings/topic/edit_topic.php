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
			$getSmsTemplate = $conoracle->prepare("SELECT ID_MATCHING FROM smstopicmatchtemplate WHERE id_submenu = :id_submenu");
			$getSmsTemplate->execute([':id_submenu'=> $dataComing["id_submenu"]]);
			$rowTemplate = $getSmsTemplate->fetch(PDO::FETCH_ASSOC);
			if(isset($rowTemplate["ID_MATCHING"]) && $rowTemplate["ID_MATCHING"] != ""){
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
				$id_matching  = $func->getMaxTable('id_matching' , 'smstopicmatchtemplate',$conoracle);
				$insertTopicMatch = $conoracle->prepare("INSERT INTO smstopicmatchtemplate(id_matching,id_submenu,id_smstemplate) 
														VALUES(:id_matching, :id_submenu,:id_smstemplate)");
				if($insertTopicMatch->execute([
					':id_matching' => $id_matching,
					':id_submenu' => $dataComing["id_submenu"],
					':id_smstemplate' => $dataComing["id_template"]
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