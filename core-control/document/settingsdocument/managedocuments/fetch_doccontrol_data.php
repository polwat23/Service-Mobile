<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		$arrayGroup = array();
		$fetchDocumentSystems = $conmysql->prepare("SELECT short_prefix, description, amount_prefix, prefix_data_type, 
												data_value_column,data_desc_column, connection_db,query_string, update_date
												FROM doccontrolid
												WHERE is_use = '1' AND short_prefix = 'L'");
		$fetchDocumentSystems->execute();
		if($fetchDocumentSystems->rowCount() > 0){
			$dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC);
			$systemsArray = array();
			$systemsArray["SHORT_PREFIX"] = $dataSystem["short_prefix"];
			$systemsArray["DESCRIPTION"] = $dataSystem["description"];
			$systemsArray["AMOUNT_PREFIX"] = $dataSystem["amount_prefix"];
			$systemsArray["PREFIX_DATA_TYPE"] = $dataSystem["prefix_data_type"];
			$systemsArray["DATA_VALUE_COLUMN"] = $dataSystem["data_value_column"];
			$systemsArray["DATA_DESC_COLUMN"] = $dataSystem["data_desc_column"];
			$systemsArray["CONNECTION_DB"] = $dataSystem["connection_db"];
			$systemsArray["QUERY_STRING"] = $dataSystem["query_string"];
			$systemsArray["UPDATE_DATE"] = $lib->convertdate($dataSystem["update_date"],'d m Y',true); 
			
			if($dataSystem["connection_db"] == "oracle"){
				$query_string = $dataSystem["query_string"];
				$fetchColumn = $conoracle->prepare($query_string);
				$fetchColumn->execute();
				while($rowColumn = $fetchColumn->fetch(PDO::FETCH_ASSOC)){
					$arrayData = array();
					$arrayData[$dataSystem["data_value_column"]] = $rowColumn[$dataSystem["data_value_column"]];
					if(isset($dataSystem["data_desc_column"]) || $dataSystem["data_desc_column"] != ""){
					$arrayData[$dataSystem["data_desc_column"]] = $rowColumn[$dataSystem["data_desc_column"]];
					}
					$arrayGroup[] = $arrayData;
				}
			}else{
			}
		}
		$arrayResult['CONTROLCOLUMN_DATA'] = $arrayGroup;
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

