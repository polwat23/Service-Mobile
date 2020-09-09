<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_accountconstant'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$deleteConstants = $conmysql->prepare("UPDATE gcconstantaccountdept SET is_use = '-9'
												WHERE id_accountconstant = :id_accountconstant");
		if($deleteConstants->execute([
			':id_accountconstant' => $dataComing["id_accountconstant"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบค่าคงที่ประเภทบัญชีเงินฝากนี้ได้ กรุณาติดต่อผู้พัฒนา";
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