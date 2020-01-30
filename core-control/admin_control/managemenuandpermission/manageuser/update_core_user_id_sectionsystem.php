<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_section_system'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','manageuser')){
		$updatemenu = $conmysql->prepare("UPDATE coreuser 
										  SET id_section_system = :id_section_system
								          WHERE  username = :username;");
		if($updatemenu->execute([
			':id_section_system' => $dataComing["id_section_system"],
			':username' => $dataComing["username"]
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