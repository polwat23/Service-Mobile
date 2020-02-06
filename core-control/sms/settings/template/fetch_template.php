<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate') || $func->check_permission_core($payload,'sms','managetopic')){
		$arrTemplateGroup = array();
		if(isset($dataComing["id_smstemplate"])){
			$fetchTemplate = $conmysql->prepare("SELECT st.id_smstemplate,st.smstemplate_name,st.smstemplate_body,sq.id_smsquery,sq.sms_query,
												sq.column_selected,sq.target_field,sq.is_bind_param,sq.condition_target
												FROM smstemplate st LEFT JOIN smsquery sq ON st.id_smsquery = sq.id_smsquery
												WHERE st.is_use = '1' and st.id_smstemplate = :id_smstemplate");
			$fetchTemplate->execute([':id_smstemplate' => $dataComing["id_smstemplate"]]);
			$rowTemplate = $fetchTemplate->fetch();
			$arrTemplateGroup["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
			$arrTemplateGroup["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
			$arrTemplateGroup["TEMPLATE_BODY"] = $rowTemplate["smstemplate_body"];
			$arrTemplateGroup["ID_SMSQUERY"] = $rowTemplate["id_smsquery"];
			$arrTemplateGroup["SMS_QUERY"] = $rowTemplate["sms_query"];
			$arrTemplateGroup["COLUMN_SELECTED"] = explode(',',$rowTemplate["column_selected"]);
			$arrTemplateGroup["TARGET_FIELD"] = $rowTemplate["target_field"];
			$arrTemplateGroup["CONDITION_TARGET"] = (explode('.',$rowTemplate["condition_target"]))[0];
			$arrTemplateGroup["BIND_PARAM"] = $rowTemplate["is_bind_param"];
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