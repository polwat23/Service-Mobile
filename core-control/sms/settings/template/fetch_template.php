<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate',$conoracle) || $func->check_permission_core($payload,'sms','managetopic',$conoracle)
		|| $func->check_permission_core($payload,'sms','reportsmssuccess',$conoracle)){
		$arrTemplateGroup = array();
		if(isset($dataComing["id_smstemplate"])){
			$fetchTemplate = $conoracle->prepare("SELECT st.id_smstemplate,st.smstemplate_name,st.smstemplate_body,sq.id_smsquery,sq.sms_query,sq.set_column,st.spec_person,
												sq.column_selected,sq.target_field,sq.is_bind_param,sq.condition_target,sq.is_stampflag,sq.stamp_table,sq.where_stamp
												FROM smstemplate st LEFT JOIN smsquery sq ON st.id_smsquery = sq.id_smsquery
												WHERE st.is_use = '1' and st.id_smstemplate = :id_smstemplate");
			$fetchTemplate->execute([':id_smstemplate' => $dataComing["id_smstemplate"]]);
			$rowTemplate = $fetchTemplate->fetch(PDO::FETCH_ASSOC);
			$arrTemplateGroup["ID_TEMPLATE"] = $rowTemplate["ID_SMSTEMPLATE"];
			$arrTemplateGroup["TEMPLATE_NAME"] = $rowTemplate["SMSTEMPLATE_NAME"];
			$arrTemplateGroup["TEMPLATE_BODY"] = $rowTemplate["SMSTEMPLATE_BODY"];
			$arrTemplateGroup["ID_SMSQUERY"] = $rowTemplate["ID_SMSQUERY"];
			$arrTemplateGroup["SMS_QUERY"] = $rowTemplate["SMS_QUERY"];
			$arrTemplateGroup["COLUMN_SELECTED"] = explode(',',$rowTemplate["COLUMN_SELECTED"]);
			$arrTemplateGroup["TARGET_FIELD"] = $rowTemplate["SPEC_PERSON"] == '1' ? true : false;
			$arrTemplateGroup["CONDITION_TARGET"] = $rowTemplate["CONDITION_TARGET"];
			$arrTemplateGroup["IS_STAMPFLAG"] = $rowTemplate["IS_STAMPFLAG"];
			$arrTemplateGroup["STAMP_TABLE"] = $rowTemplate["STAMP_TABLE"];
			$arrTemplateGroup["SET_COLUMN"] = $rowTemplate["SET_COLUMN"];
			$arrTemplateGroup["WHERE_STAMP"] = $rowTemplate["WHERE_STAMP"];
			$arrTemplateGroup["BIND_PARAM"] = $rowTemplate["IS_BIND_PARAM"];
		}else{
			$fetchTemplate = $conoracle->prepare("SELECT id_smstemplate,smstemplate_name,smstemplate_body
												FROM smstemplate
												WHERE is_use = '1' ORDER BY id_smstemplate DESC");
			$fetchTemplate->execute();
			while($rowTemplate = $fetchTemplate->fetch(PDO::FETCH_ASSOC)){
				$arrTemplate = array();
				$arrTemplate["ID_TEMPLATE"] = $rowTemplate["ID_SMSTEMPLATE"];
				$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["SMSTEMPLATE_NAME"];
				$arrTemplate["TEMPLATE_BODY"] = $rowTemplate["SMSTEMPLATE_BODY"];
				$arrTemplateGroup[] = $arrTemplate;
			}
		}
		$arrayResult['TEMPLATE'] = $arrTemplateGroup;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>