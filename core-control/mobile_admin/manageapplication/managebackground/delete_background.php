<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_background'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managebackground')){
	$updatemenu = $conmysql->prepare("UPDATE  gcconstantbackground  SET is_use = '-9'
										  WHERE id_background=:id_background");
		if($updatemenu->execute([
			':id_background' => $dataComing["id_background"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบรูปได้ กรุณาติดต่อผู้พัฒนา";
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