<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic')){
		$conmssql->beginTransaction();
		$unuseSubMenu = $conmssql->prepare("UPDATE coresubmenu SET menu_status = '-9' WHERE id_submenu = :id_submenu");
		if($unuseSubMenu->execute([
			':id_submenu' => $dataComing["id_submenu"]
		])){
			$unuseTopic = $conmssql->prepare("UPDATE smstopicmatchtemplate SET is_use = '-9' WHERE id_submenu = :id_submenu");
			if($unuseTopic->execute([
				':id_submenu' => $dataComing["id_submenu"]
			])){
				$unGrantPermission = $conmssql->prepare("UPDATE corepermissionsubmenu SET is_use = '-9' WHERE id_submenu = :id_submenu");
				if($unGrantPermission->execute([':id_submenu' => $dataComing["id_submenu"]])){
					$conmssql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$conmssql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถลบหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$conmssql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$conmssql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถลบหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
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