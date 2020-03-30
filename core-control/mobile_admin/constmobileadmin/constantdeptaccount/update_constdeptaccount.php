<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_accountconstant','id_palette','dept_type_desc','allow_transaction'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$updateConstants = $conmysql->prepare("UPDATE gcconstantaccountdept SET id_palette = :id_palette, dept_type_desc = :dept_type_desc, allow_transaction = :allow_transaction
												WHERE id_accountconstant = :id_accountconstant");
		if($updateConstants->execute([
			':id_palette' => $dataComing["id_palette"],
			':id_accountconstant' => $dataComing["id_accountconstant"],
			':dept_type_desc' => $dataComing["dept_type_desc"],
			':allow_transaction' => $dataComing["allow_transaction"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ประเภทบัญชีเงินฝากนี้ได้ กรุณาติดต่อผู้พัฒนา";
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