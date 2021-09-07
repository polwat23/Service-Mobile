<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','section','minimum_value'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		if(isset($dataComing["minimum_value"]) || $dataComing["minimum_value"] != ''){
			$updateConstants = $conmssql->prepare("INSERT INTO gcconstantapprwithdrawal(minimum_value, maximum_value, member_no, id_section_system) 
																	VALUES (:minimum_value,:maximum_value,:member_no,:id_section_system)");
			if($updateConstants->execute([
				':minimum_value' => $dataComing["minimum_value"],
				':maximum_value' => isset($dataComing["maximum_value"]) || $dataComing["maximum_value"] != '' ? $dataComing["maximum_value"]  :  null ,
				':member_no' => $dataComing["member_no"],
				':id_section_system' => $dataComing["section"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่เป็นค่าว่างได้";
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