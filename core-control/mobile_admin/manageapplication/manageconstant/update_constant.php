<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_constant','value_constant'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageconstant')){
		if(isset($dataComing["value_constant"]) || $dataComing["value_constant"] != ''){
			$updateConstants = $conmysql->prepare("UPDATE gcconstant SET constant_value = :constant_value
													WHERE id_constant = :id_constant");
			if($updateConstants->execute([
				':constant_value' => $dataComing["value_constant"],
				':id_constant' => $dataComing["id_constant"]
			])){
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult["RESULT"] = FALSE;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "4003";
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเป็นค่าว่างได้";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>