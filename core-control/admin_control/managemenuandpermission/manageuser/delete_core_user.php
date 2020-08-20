<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managecoreusers')){
		$deletecoreuser = $conmysql->prepare("UPDATE coreuser SET user_status = '-9'
										  WHERE username=:username");
		if($deletecoreuser->execute([
			':username' => $dataComing["username"]
		])){
			$arrayStruc = [
					':menu_name' => "manageuser",
					':username' => $payload["username"],
					':use_list' => "delete core user",
					':details' => 'username '.$dataComing["username"].' was deleted'
				];
			
			$log->writeLog('editadmincontrol',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบผู้ใช้ได้ กรุณาติดต่อผู้พัฒนา";
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