<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','dept_type_code','dept_type_desc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$insertConstants = $conmysql->prepare("INSERT gcconstantaccountdept(dept_type_code,member_cate_code,dept_type_desc,id_palette)
												VALUES(:depttype_code,'AL',:depttype_desc,'2')");
		if($insertConstants->execute([
			':depttype_code' => $dataComing["dept_type_code"],
			':depttype_desc' => $dataComing["dept_type_desc"]
		])){
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ประเภทบัญชีเงินฝากนี้ได้ กรุณาติดต่อผู้พัฒนา";
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