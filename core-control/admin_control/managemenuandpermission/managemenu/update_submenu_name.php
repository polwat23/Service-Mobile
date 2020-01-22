<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_name','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managemenu')){
		$updatemenu = $conmysql->prepare("UPDATE coresubmenu SET menu_name = :menu_name
									 WHERE id_submenu = :id_submenu");
		if($updatemenu->execute([
			':menu_name' => $dataComing["menu_name"],
			':id_submenu' => $dataComing["id_submenu"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขชื่อเมนูได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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