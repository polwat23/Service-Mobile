<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedoccontrolid')){
			$insertDocumentSystems = $conmysql->prepare("UPDATE doccontrolid SET short_prefix = :short_prefix,description = :description, amount_prefix = :amount_prefix, prefix_data_type = :prefix_data_type, 
													data_value_column = :data_value_column, data_desc_column = :data_desc_column, connection_db = :connection_db, query_string = :query_string
													WHERE short_prefix = :update_short_prefix");
			if($insertDocumentSystems->execute([
				':short_prefix' =>  $dataComing["short_prefix"],
				':description' =>  $dataComing["description"],
				':amount_prefix' =>  $dataComing["amount_prefix"],
				':prefix_data_type' =>  $dataComing["prefix_data_type"],
				':data_value_column' => (isset($dataComing["data_value_column"]) && $dataComing["data_value_column"] != "") ? $dataComing["data_value_column"] : NULL,
				':data_desc_column' => (isset($dataComing["data_desc_column"]) && $dataComing["data_desc_column"] != "") ? $dataComing["data_desc_column"] : NULL,
				':connection_db' => (isset($dataComing["connection_db"]) && $dataComing["connection_db"] != "") ? $dataComing["connection_db"] : NULL,
				':query_string' => (isset($dataComing["query_string"]) && $dataComing["query_string"] != "") ? $dataComing["query_string"] : NULL,
				':update_short_prefix' =>  $dataComing["update_short_prefix"],
			])){				
				$arrayStruc = [
					':menu_name' => "managedoccontrolid",
					':username' => $payload["username"],
					':use_list' =>"Edit controlid",
					':details' => "Update short_prefix on ".$dataComing["update_short_prefix"]." to ".$dataComing["short_prefix"].
								";description = ".$dataComing["description"].
								";amount_prefix = ".$dataComing["amount_prefix"].
								";prefix_data_type = ".$dataComing["prefix_data_type"].
								";data_value_column = ".$dataComing["data_value_column"].
								";data_desc_column = ".$dataComing["data_desc_column"].
								";connection_db = ".$dataComing["connection_db"].
								";query_string = ".$dataComing["query_string"]
				];
				$log->writeLog('editdocument',$arrayStruc);	

				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
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

