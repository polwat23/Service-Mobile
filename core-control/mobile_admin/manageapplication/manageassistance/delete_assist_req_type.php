<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_const_welfare'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageassistance')){
		$updateform = $conmysql->prepare("UPDATE gcconstantwelfare SET is_use = '0' WHERE id_const_welfare = :id_const_welfare");
		if($updateform->execute([
			':id_const_welfare' => $dataComing["id_const_welfare"]
		])){
			$arrayStruc = [
				':menu_name' => "manageassistance",
				':username' => $payload["username"],
				':use_list' => "delete constwelfare",
				':details' => "delete constwelfare (id ".$dataComing["id_const_welfare"].")"
			];
			
			$log->writeLog('manageapplication',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเลบรายการนี้ได้ กรุณาติดต่อผู้พัฒนา";
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