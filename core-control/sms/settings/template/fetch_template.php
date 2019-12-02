<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate',$conmysql)){
		$arrTemplateGroup = array();
		if(isset($dataComing["id_smstemplate"])){
			$fetchTemplate = $conmysql->prepare("SELECT id_smstemplate,smstemplate_name,smstemplate_body
												FROM smstemplate
												WHERE is_use = '1' and id_smstemplate = :id_smstemplate ORDER BY id_smstemplate DESC");
			$fetchTemplate->execute([':id_smstemplate' => $dataComing["id_smstemplate"]]);
			$rowTemplate = $fetchTemplate->fetch();
			$arrTemplateGroup["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
			$arrTemplateGroup["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
			$arrTemplateGroup["TEMPLATE_BODY"] = $rowTemplate["smstemplate_body"];
		}else{
			$fetchTemplate = $conmysql->prepare("SELECT id_smstemplate,smstemplate_name,smstemplate_body
												FROM smstemplate
												WHERE is_use = '1' ORDER BY id_smstemplate DESC");
			$fetchTemplate->execute();
			while($rowTemplate = $fetchTemplate->fetch()){
				$arrTemplate = array();
				$arrTemplate["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
				$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
				$arrTemplate["TEMPLATE_BODY"] = $rowTemplate["smstemplate_body"];
				$arrTemplateGroup[] = $arrTemplate;
			}
		}
		$arrayResult['TEMPLATE'] = $arrTemplateGroup;
		$arrayResult['RESULT'] = TRUE;
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