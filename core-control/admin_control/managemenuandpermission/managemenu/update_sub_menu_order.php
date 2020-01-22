<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_list'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$conmysql->beginTransaction();
		foreach($dataComing["menu_list"] as $menu_list){
			$updatemenu = $conmysql->prepare("UPDATE coresubmenu SET menu_order = :menu_order
										 WHERE id_submenu = :id_submenu");
			if($updatemenu->execute([
				':menu_order' => $menu_list["order"],
				':id_submenu' => $menu_list["menu_id"]
			])){
				continue;
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถจัดเรียงเมนูได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
		$conmysql->commit();
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);	
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