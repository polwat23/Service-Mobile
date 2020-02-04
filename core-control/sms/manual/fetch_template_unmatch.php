<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
		$arrGroupTemplate = array();
		$getTemplateUnMatch = $conmysql->prepare("SELECT id_smstemplate,smstemplate_name,smstemplate_body
													FROM smstemplate 
													WHERE is_use = '1' and id_smstemplate NOT IN(SELECT id_smstemplate FROM smstopicmatchtemplate WHERE is_use <> '-9')
													and id_smsquery IS NULL");
		$getTemplateUnMatch->execute();
		if($getTemplateUnMatch->rowCount() > 0){
			while($rowTemplate = $getTemplateUnMatch->fetch()){
				$arrTemplate = array();
				$arrTemplate["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
				$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
				$arrTemplate["TEMPLATE_MESSAGE"] = $rowTemplate["smstemplate_body"];
				$arrGroupTemplate[] = $arrTemplate;
			}
			$arrayResult["TEMPLATE"] = $arrGroupTemplate;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
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