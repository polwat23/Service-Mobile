<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_accountconstant','is_use'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$updateConstants = $conmysql->prepare("UPDATE gcconstantaccountdept SET is_use = :is_use
												WHERE id_accountconstant = :id_accountconstant");
		if($updateConstants->execute([
			':is_use' => $dataComing["is_use"],
			':id_accountconstant' => $dataComing["id_accountconstant"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขการเปิดใช้งานประเภทบัญชีได้ กรุณาติดต่อผู้พัฒนา";
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