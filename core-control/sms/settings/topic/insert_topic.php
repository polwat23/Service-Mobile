<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_template','topic_name'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic') && is_numeric($dataComing["id_template"])){
		$conoracle->beginTransaction();
		$page_name = $lib->randomText('all',6);
		$id_submenu  = $func->getMaxTable('id_submenu' , 'coresubmenu');	
		$insertSmsMenu = $conoracle->prepare("INSERT INTO coresubmenu(id_submenu,menu_name,page_name,menu_order,create_by,id_menuparent,id_coremenu)
												VALUES(:id_submenu,:topic_name,:page_name,1,:username,8,1)");
		if($insertSmsMenu->execute([
			':id_submenu'=> $id_submenu,
			':topic_name' => $dataComing["topic_name"],
			':page_name'=> $page_name,
			':username' => $payload["username"]
		])){
				
			$id_matching  = $func->getMaxTable('id_matching' , 'smstopicmatchtemplate');
			$insertTopicMatch = $conoracle->prepare("INSERT INTO smstopicmatchtemplate(id_matching,id_submenu,id_smstemplate) 
													VALUES(:id_matching, :id_submenu,:id_smstemplate)");
			if($insertTopicMatch->execute([
				':id_matching' => $id_matching,
				':id_submenu' => $id_submenu,
				':id_smstemplate' => $dataComing["id_template"]
			])){
				$conoracle->commit();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$conoracle->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเชื่อมเทมเพลตกับหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$conoracle->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
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
