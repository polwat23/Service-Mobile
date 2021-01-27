<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$updateConstants = $conmysql->prepare("UPDATE gcconstantapprwithdrawal SET minimum_value = :minimum_value, maximum_value = :maximum_value, member_no = :member_no, id_section_system = :id_section_system WHERE id_apprwd_constant = :id_apprwd_constant");
		if($updateConstants->execute([
			':minimum_value' => $dataComing["minimum_value"],
			':maximum_value' => isset($dataComing["maximum_value"]) || $dataComing["maximum_value"] != '' ? $dataComing["maximum_value"]  :  null ,
			':member_no' => $dataComing["member_no"],
			':id_section_system' => $dataComing["section"],
			':id_apprwd_constant'  => $dataComing["id_apprwd_constant"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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