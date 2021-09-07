<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		$arrayDocument = array();
		$fetchDocumentSystems = $conmssql->prepare("SELECT short_prefix, description, amount_prefix, prefix_data_type, data_value_column, data_desc_column, connection_db, query_string
												FROM doccontrolid
												WHERE is_use = '1'");
		$fetchDocumentSystems->execute();
		while($dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC)){
			$systemsArray = array();
			$systemsArray["SHORT_PREFIX"] = $dataSystem["short_prefix"];
			$systemsArray["DESCRIPTION"] = $dataSystem["description"];
			$systemsArray["AMOUNT_PREFIX"] = $dataSystem["amount_prefix"];
			$systemsArray["PREFIX_DATA_TYPE"] = $dataSystem["prefix_data_type"];
			$systemsArray["DATA_VALUE_COLUMN"] = $dataSystem["data_value_column"];
			$systemsArray["DATA_DESC_COLUMN"] = $dataSystem["data_desc_column"];
			$systemsArray["CONNECTION_DB"] = $dataSystem["connection_db"];
			$systemsArray["QUERY_STRING"] = $dataSystem["query_string"];
			$arrayDocument[] = $systemsArray;
		}
		$arrayResult['DOC_CONTROLID'] = $arrayDocument;
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

