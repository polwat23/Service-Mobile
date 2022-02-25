<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_uploadfilecoop'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuploadfiles')){
	$updatemenu = $conmysql->prepare("UPDATE  gcuploadfilecoop  SET is_use = '0'
										  WHERE id_uploadfilecoop=:id_uploadfilecoop");
		if($updatemenu->execute([
			':id_uploadfilecoop' => $dataComing["id_uploadfilecoop"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบไฟล์นี้ได้ กรุณาติดต่อผู้พัฒนา";
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