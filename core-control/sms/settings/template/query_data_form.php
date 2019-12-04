<?php
ini_set("memory_limit","-1");
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','query_message_spc_'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		if(strtolower(substr($dataComing["query_message_spc_"],0,6)) === "select"){
			$arrayData = array();
			$arrColumn = array();
			$queryDataForm = $conoracle->prepare($dataComing["query_message_spc_"]);
			$queryDataForm->execute();
			$dataForm = $queryDataForm->fetch(PDO::FETCH_ASSOC);
			if(isset($dataForm) && $dataForm){
				$arrColumn = array_keys($dataForm);
			}
			$arrayResult['COLUMN'] = $arrColumn;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "4000";
			$arrayResult['RESPONSE_AWARE'] = "select";
			$arrayResult['RESPONSE'] = "Command execute not allow";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
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