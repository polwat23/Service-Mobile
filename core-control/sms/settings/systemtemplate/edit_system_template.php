<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','subject','detail_root_','id_systemplate','is_use'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managesystemtemplate')){
		$updateSysTemplate = $conmysql->prepare("UPDATE smssystemtemplate SET subject = :subject,body = :body,is_use = :is_use WHERE id_systemplate = :id_systemplate");
		if($updateSysTemplate->execute([
			':subject' => $dataComing["subject"],
			':body' => $dataComing["detail_root_"],
			':is_use' => $dataComing["is_use"],
			':id_systemplate' => $dataComing["id_systemplate"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตระบบได้ กรุณาติดต่อผู้พัฒนา";
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