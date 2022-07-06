<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','template_name','template_body'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate',$conoracle)){
		$id_smsquery = null;
		$conoracle->beginTransaction();	
		$id_smsquery  = $func->getMaxTable('id_smsquery' , 'smsquery',$conoracle);
		if(isset($dataComing["query_template_spc_"]) && isset($dataComing["column_selected"]) && sizeof($dataComing["column_selected"]) > 0){
			if($dataComing["is_stampflag"] == '1'){
				if(empty($dataComing["condition_target"])){
					
					$insertSmsQuery = $conoracle->prepare("INSERT INTO smsquery(id_smsquery,sms_query,column_selected,target_field,is_stampflag,stamp_table,where_stamp,set_column,create_by)
															VALUES(:id_smsquery,:sms_query,:column_selected,:target_field,'1',:stamp_table,:where_stamp,:set_column,:username)");
					if($insertSmsQuery->execute([
						':id_smsquery' => $id_smsquery,
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':stamp_table' => $dataComing["stamp_table"],
						':where_stamp' => $dataComing["where_stamp"]." and 1=1",
						':set_column' => $dataComing["set_column"],
						':username' => $payload["username"]
					])){
						
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}else{
					$insertSmsQuery = $conoracle->prepare("INSERT INTO smsquery(id_smsquery,sms_query,column_selected,target_field,condition_target,is_stampflag,stamp_table,where_stamp,set_column,is_bind_param,create_by)
															VALUES(:id_smsquery,:sms_query,:column_selected,:target_field,:condition_target,'1',:stamp_table,:where_stamp,'1',:username)");
					if($insertSmsQuery->execute([
						':id_smsquery' => $id_smsquery,
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':stamp_table' => $dataComing["stamp_table"],
						':where_stamp' => $dataComing["where_stamp"]." and 1=1",
						':set_column' => $dataComing["set_column"],
						':username' => $payload["username"]
					])){

					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}
			}else{
				if(empty($dataComing["condition_target"])){
					$insertSmsQuery = $conoracle->prepare("INSERT INTO smsquery(id_smsquery,sms_query,column_selected,target_field,create_by)
															VALUES(:id_smsquery,:sms_query,:column_selected,:target_field,:username)");
					if($insertSmsQuery->execute([
						':id_smsquery' => $id_smsquery,
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':username' => $payload["username"]
					])){
	
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}else{
					$insertSmsQuery = $conoracle->prepare("INSERT INTO smsquery(id_smsquery,sms_query,column_selected,target_field,condition_target,is_bind_param,create_by)
															VALUES(:id_smsquery,:sms_query,:column_selected,:target_field,:condition_target,'1',:username)");
					if($insertSmsQuery->execute([
						':id_smsquery' => $id_smsquery,
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':username' => $payload["username"]
					])){
	
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = json_encode([
						':id_smsquery' => $id_smsquery,
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':username' => $payload["username"]
					]);//"ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}
			}
		}
		$id_smstemplate  = $func->getMaxTable('id_smstemplate' , 'smstemplate',$conoracle);	
		$insertTemplate = $conoracle->prepare("INSERT INTO smstemplate(id_smstemplate,smstemplate_name,smstemplate_body,create_by,id_smsquery) 
												VALUES(:id_smstemplate, :smstemplate_name,:smstemplate_body,:username,:id_smsquery)");
		if($insertTemplate->execute([
			':id_smstemplate' => $id_smstemplate,
			':smstemplate_name' => $dataComing["template_name"],
			':smstemplate_body' => $dataComing["template_body"],
			':username' => $payload["username"],
			':id_smsquery' => $id_smsquery
		])){
			$conoracle->commit();
			
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$conoracle->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
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