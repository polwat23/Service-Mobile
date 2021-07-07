<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','page_name'],$dataComing)){
	if($func->check_permission_core($payload,'sms',$dataComing["page_name"])){
		$getTemplate = $conoracle->prepare("SELECT stp.id_smstemplate,stp.smstemplate_name,stp.smstemplate_body,sq.id_smsquery,smu.menu_name,sq.is_bind_param,
											sq.condition_target,sq.target_field
											FROM coresubmenu smu INNER JOIN smstopicmatchtemplate smt ON smu.id_submenu = smt.id_submenu
											INNER JOIN smstemplate stp ON smt.id_smstemplate = stp.id_smstemplate 
											LEFT JOIN smsquery sq ON stp.id_smsquery = sq.id_smsquery WHERE smu.page_name = :page_name and smu.menu_status = '1'
											and stp.is_use = '1' and smt.is_use = '1'");
		$getTemplate->execute([':page_name' => $dataComing["page_name"]]);
		$rowTemplate = $getTemplate->fetch(PDO::FETCH_ASSOC);
		if(	isset($rowTemplate["ID_SMSTEMPLATE"])){
			$arrTemplate = array();
			$arrTemplate["ID_TEMPLATE"] = $rowTemplate["ID_SMSTEMPLATE"];
			$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["SMSTEMPLATE_NAME"];
			$arrTemplate["TEMPLATE_MESSAGE"] = $rowTemplate["SMSTEMPLATE_BODY"];
			$arrTemplate["ID_SMSQUERY"] = $rowTemplate["ID_SMSQUERY"];
			$arrTemplate["MENU_NAME"] = $rowTemplate["MENU_NAME"];
			$arrTemplate["BIND_PARAM"] = $rowTemplate["IS_BIND_PARAM"];
			$arrTemplate["CONDITION_TARGET"] = $rowTemplate["CONDITION_TARGET"];
			$arrTemplate["TARGET_FIELD"] = $rowTemplate["TARGET_FIELD"];
			$arrayResult["DATA"] = $arrTemplate;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			http_response_code(204);
			require_once('../../../include/exit_footer.php');
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