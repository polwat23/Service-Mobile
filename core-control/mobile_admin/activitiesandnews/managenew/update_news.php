<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_status','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
	
			$updatemenu = $conmysql->prepare("UPDATE coresubmenu SET menu_status = '0'
										 WHERE id_submenu = :id_submenu");
			if($updatemenu->execute([
				':id_submenu' => $dataComing["id_submenu"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะเมนูได้ กรุณาติดต่อผู้พัฒนา#1 ";
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