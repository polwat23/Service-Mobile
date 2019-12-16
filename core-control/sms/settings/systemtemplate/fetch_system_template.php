<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managesystemtemplate')){
		$arrGroupSysTemplate = array();
		$fetchSysTemplate = $conmysql->prepare("SELECT subject,body,id_systemplate FROM smssystemtemplate WHERE is_use = '1'");
		$fetchSysTemplate->execute();
		if($fetchSysTemplate->rowCount() > 0){
			while($rowSysTemplate = $fetchSysTemplate->fetch()){
				$arraySysTem = array();
				$arraySysTem["SUBJECT"] = $rowSysTemplate["subject"];
				$arraySysTem["BODY"] = $rowSysTemplate["body"];
				$arraySysTem["ID_SYSTEMPLATE"] = $rowSysTemplate["id_systemplate"];
				$arrGroupSysTemplate[] = $arraySysTem;
			}
			$arrayResult['SYSTEM_TEMPLATE'] = $arrGroupSysTemplate;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
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