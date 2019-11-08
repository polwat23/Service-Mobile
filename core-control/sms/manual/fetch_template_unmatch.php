<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && 
$lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'sms',$conmysql)){
		$getTemplateUnMatch = $conmysql->prepare("SELECT stp.id_smstemplate,stp.smstemplate_name,stp.smstemplate_body,sq.id_smsquery
											FROM smstemplate stp LEFT JOIN smsquery sq ON stp.id_smsquery = sq.id_smsquery 
                                            WHERE stp.is_use = '1' and stp.id_smstemplate NOT IN(SELECT id_smstemplate FROM smstopicmatchtemplate )");
		$getTemplateUnMatch->execute();
		if($getTemplateUnMatch->rowCount() > 0){
			$arrTemplate = array();
			$rowTemplate = $getTemplateUnMatch->fetch();
			$arrTemplate["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
			$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
			$arrTemplate["TEMPLATE_MESSAGE"] = $rowTemplate["smstemplate_body"];
			$arrTemplate["ID_SMSQUERY"] = $rowTemplate["id_smsquery"];
			$arrGroupTemplate[] = $arrTemplate;
			$arrayResult["DATA"] = $arrGroupTemplate;
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