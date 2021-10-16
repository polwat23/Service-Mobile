<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall',$conoracle) 
		|| $func->check_permission_core($payload,'sms','sendmessageperson',$conoracle)){
		$arrGroupTemplate = array();
		$getTemplateUnMatch = $conoracle->prepare("SELECT id_smstemplate,smstemplate_name,smstemplate_body
													FROM smstemplate 
													WHERE is_use = '1' and id_smstemplate NOT IN(SELECT id_smstemplate FROM smstopicmatchtemplate WHERE is_use <> '-9')
													and id_smsquery IS NULL");
		$getTemplateUnMatch->execute();
		while($rowTemplate = $getTemplateUnMatch->fetch(PDO::FETCH_ASSOC)){
			$arrTemplate = array();
			$arrTemplate["ID_TEMPLATE"] = $rowTemplate["ID_SMSTEMPLATE"];
			$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["SMSTEMPLATE_NAME"];
			$arrTemplate["TEMPLATE_MESSAGE"] = $rowTemplate["SMSTEMPLATE_BODY"];
			$arrGroupTemplate[] = $arrTemplate;
		}
		$arrayResult["TEMPLATE"] = $arrGroupTemplate;
		
		if(sizeof($arrGroupTemplate) > 0){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			http_response_code(204);
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>