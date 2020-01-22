<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessage')){
		$arrGroupTemplate = array();
		$getTemplateUnMatch = $conmysql->prepare("SELECT stp.id_smstemplate,stp.smstemplate_name,stp.smstemplate_body,sq.id_smsquery,
													sq.is_bind_param
													FROM smstemplate stp LEFT JOIN smsquery sq ON stp.id_smsquery = sq.id_smsquery 
													WHERE stp.is_use = '1' and stp.id_smstemplate NOT IN(SELECT id_smstemplate FROM smstopicmatchtemplate WHERE is_use <> '-9')");
		$getTemplateUnMatch->execute();
		if($getTemplateUnMatch->rowCount() > 0){
			while($rowTemplate = $getTemplateUnMatch->fetch()){
				$arrTemplate = array();
				$arrTemplate["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
				$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
				$arrTemplate["TEMPLATE_MESSAGE"] = $rowTemplate["smstemplate_body"];
				$arrTemplate["ID_SMSQUERY"] = $rowTemplate["id_smsquery"];
				$arrTemplate["BIND_PARAM"] = $rowTemplate["is_bind_param"];
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