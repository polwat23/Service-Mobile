<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','password','id_section_system','create_date','update_date','user_status'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','manageuser')){
		$updatemenu = $conmysql->prepare("INSERT INTO coreuser (username, password, id_section_system, create_date, update_date, user_status) 
										 VALUES(:username,:password,:id_section_system,:create_date,:update_date,:user_status)");
		if($updatemenu->execute([
			':username' => $dataComing["username"],
			':password' => $dataComing["password"],
			':id_section_system' => $dataComing["id_section_system"],
			':create_date' => $dataComing["create_date"],
			':update_date' => $dataComing["update_date"],
			':user_status' => $dataComing["user_status"]
			
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามาเพิ่มผู้ใช้งานได้กรุณาติดต่อผู้พัฒนา";
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