<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','field','value','id_systemplate'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managesystemtemplate')){
		$updateSysTemplate = $conmysql->prepare("UPDATE smssystemtemplate SET ".$dataComing["field"]." = :value WHERE id_systemplate = :id_systemplate");
		if($updateSysTemplate->execute([
			':value' => $dataComing["value"],
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