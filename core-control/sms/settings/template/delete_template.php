<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_smstemplate'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		$unuseTemplate = $conmysql->prepare("UPDATE smstemplate SET is_use = '-9' WHERE id_smstemplate = :id_smstemplate");
		if($unuseTemplate->execute([
			':id_smstemplate' => $dataComing["id_smstemplate"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
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