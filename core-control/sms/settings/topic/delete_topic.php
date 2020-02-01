<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic')){
		$conmysql->beginTransaction();
		$unuseSubMenu = $conmysql->prepare("UPDATE coresubmenu SET menu_status = '-9' WHERE id_submenu = :id_submenu");
		if($unuseSubMenu->execute([
			':id_submenu' => $dataComing["id_submenu"]
		])){
			$unuseTopic = $conmysql->prepare("UPDATE smstopicmatchtemplate SET is_use = '-9' WHERE id_submenu = :id_submenu");
			if($unuseTopic->execute([
				':id_submenu' => $dataComing["id_submenu"]
			])){
				$unGrantPermission = $conmysql->prepare("UPDATE corepermissionsubmenu SET is_use = '-9' WHERE id_submenu = :id_submenu");
				if($unGrantPermission->execute([':id_submenu' => $dataComing["id_submenu"]])){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถลบหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถลบหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>