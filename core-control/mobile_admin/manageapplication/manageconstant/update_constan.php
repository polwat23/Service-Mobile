<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_constant','value_constant'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageapplication','manageconstant',$conmysql)){
		$constants = $con->prepare("UPDATE gcconstant SET constant_value = :value_constant
									 WHERE id_constant = :id_constant");
		if($constants->execute([
				':constant_value' => $constant_value,
				':id_constant' => $id_constant
			])){
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult["RESULT"] = FALSE;
			}
		echo json_encode($arrayResult);	
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