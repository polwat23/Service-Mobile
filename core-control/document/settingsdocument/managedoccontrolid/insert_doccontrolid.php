<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedoccontrolid')){
		$checkControlID = $conmysql->prepare("SELECT short_prefix,is_use
												FROM doccontrolid
												WHERE short_prefix = :short_prefix");
		$checkControlID->execute([
			':short_prefix' =>  $dataComing["short_prefix"]
		]);
		
		if($checkControlID->rowCount() > 0){
			$rowCheckControlID = $checkControlID->fetch(PDO::FETCH_ASSOC);
			if($rowCheckControlID["is_use"] == '0'){
				$insertDocumentSystems = $conmysql->prepare("UPDATE doccontrolid SET description = :description, amount_prefix = :amount_prefix, prefix_data_type = :prefix_data_type, 
													data_value_column = :data_value_column, data_desc_column = :data_desc_column, connection_db = :connection_db, query_string = :query_string
													WHERE short_prefix = :short_prefix");
				if($insertDocumentSystems->execute([
					':description' => $dataComing["description"],
					':amount_prefix' => $dataComing["amount_prefix"],
					':prefix_data_type' => $dataComing["prefix_data_type"],
					':data_value_column' => (isset($dataComing["data_value_column"]) && $dataComing["data_value_column"] != "") ? $dataComing["data_value_column"] : NULL,
					':data_desc_column' => (isset($dataComing["data_desc_column"]) && $dataComing["data_desc_column"] != "") ? $dataComing["data_desc_column"] : NULL,
					':connection_db' => (isset($dataComing["connection_db"]) && $dataComing["connection_db"] != "") ? $dataComing["connection_db"] : NULL,
					':query_string' => (isset($dataComing["query_string"]) && $dataComing["query_string"] != "") ? $dataComing["query_string"] : NULL,
					':short_prefix' => $dataComing["short_prefix"],
				])){				
					$arrayStruc = [
						':menu_name' => "managedoccontrolid",
						':username' => $payload["username"],
						':use_list' =>"Update Deleted ControlID",
						':details' => "short_prefix = ".$dataComing["short_prefix"].
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
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['FORM_ERROR'] = "short_prefix";
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้เนื่องจากมี Key คำนำหน้านี้อยู่แล้ว";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$insertDocumentSystems = $conmysql->prepare("INSERT INTO doccontrolid(short_prefix, description, amount_prefix, 
													prefix_data_type, data_value_column, data_desc_column, connection_db, query_string) 
													VALUES (:short_prefix, :description, :amount_prefix, 
													:prefix_data_type, :data_value_column, :data_desc_column, :connection_db, :query_string)");
			if($insertDocumentSystems->execute([
				':short_prefix' =>  $dataComing["short_prefix"],
				':description' =>  $dataComing["description"],
				':amount_prefix' =>  $dataComing["amount_prefix"],
				':prefix_data_type' =>  $dataComing["prefix_data_type"],
				':data_value_column' => (isset($dataComing["data_value_column"]) && $dataComing["data_value_column"] != "") ? $dataComing["data_value_column"] : NULL,
				':data_desc_column' => (isset($dataComing["data_desc_column"]) && $dataComing["data_desc_column"] != "") ? $dataComing["data_desc_column"] : NULL,
				':connection_db' => (isset($dataComing["connection_db"]) && $dataComing["connection_db"] != "") ? $dataComing["connection_db"] : NULL,
				':query_string' => (isset($dataComing["query_string"]) && $dataComing["query_string"] != "") ? $dataComing["query_string"] : NULL,
			])){				
				$arrayStruc = [
					':menu_name' => "managedoccontrolid",
					':username' => $payload["username"],
					':use_list' =>"Add ControlID",
					':details' => "short_prefix = ".$dataComing["short_prefix"].
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
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข้อมูลได้ กรุณาติดต่อผู้พัฒนา ";
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['arr'] =	[
					':short_prefix' =>  $dataComing["short_prefix"],
					':description' =>  $dataComing["description"],
					':amount_prefix' =>  $dataComing["amount_prefix"],
					':prefix_data_type' =>  $dataComing["prefix_data_type"],
					':data_value_column' => (isset($dataComing["data_value_column"]) && $dataComing["data_value_column"] != "") ? $dataComing["data_value_column"] : NULL,
					':data_desc_column' => (isset($dataComing["data_desc_column"]) && $dataComing["data_desc_column"] != "") ? $dataComing["data_desc_column"] : NULL,
					':connection_db' => (isset($dataComing["connection_db"]) && $dataComing["connection_db"] != "") ? $dataComing["connection_db"] : NULL,
					':query_string' => (isset($dataComing["query_string"]) && $dataComing["query_string"] != "") ? $dataComing["query_string"] : NULL,
				];
				require_once('../../../../include/exit_footer.php');
			}
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

