<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','template_name','template_body','id_template'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		$conmysql->beginTransaction();
		if(isset($dataComing["id_smsquery"])){
			if(isset($dataComing["query_template_spc_"]) && isset($dataComing["column_selected"])){
				if(empty($dataComing["condition_target"])){
					$updateSmsQuery = $conmysql->prepare("UPDATE smsquery SET sms_query = :sms_query,column_selected = :column_selected,
															target_field = :target_field,is_bind_param = '0',condition_target = null WHERE id_smsquery = :id_smsquery");
					if($updateSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':id_smsquery' => $dataComing["id_smsquery"]
					])){}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "update";
						$arrayResult['RESPONSE'] = "Cannot update SMS query";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$updateSmsQuery = $conmysql->prepare("UPDATE smsquery SET sms_query = :sms_query,column_selected = :column_selected,
															target_field = :target_field,is_bind_param = '1',condition_target = :condition_target WHERE id_smsquery = :id_smsquery");
					if($updateSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':id_smsquery' => $dataComing["id_smsquery"]
					])){}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "update";
						$arrayResult['RESPONSE'] = "Cannot update SMS query";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$updateSmsQuery = $conmysql->prepare("UPDATE smsquery SET sms_query = null,column_selected = null,
														target_field = null,is_bind_param = '0',condition_target = null WHERE id_smsquery = :id_smsquery");
				if($updateSmsQuery->execute([
					':id_smsquery' => $dataComing["id_smsquery"]
				])){}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "5005";
					$arrayResult['RESPONSE_AWARE'] = "update";
					$arrayResult['RESPONSE'] = "Cannot update SMS query";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else{
			if(isset($dataComing["query_template_spc_"]) && isset($dataComing["column_selected"])){
				if(empty($dataComing["condition_target"])){
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,create_by)
															VALUES(:sms_query,:column_selected,:target_field,:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "insert";
						$arrayResult['RESPONSE'] = "Cannot insert SMS query";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,condition_target,is_bind_param,create_by)
															VALUES(:sms_query,:column_selected,:target_field,:condition_target,'1',:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $query,
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "insert";
						$arrayResult['RESPONSE'] = "Cannot insert SMS query";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}
		}
		$editTemplate = $conmysql->prepare("UPDATE smstemplate SET smstemplate_name = :smstemplate_name,smstemplate_body = :smstemplate_body
												WHERE id_smstemplate = :id_smstemplate");
		if($editTemplate->execute([
			':smstemplate_name' => $dataComing["template_name"],
			':smstemplate_body' => $dataComing["template_body"],
			':id_smstemplate' => $dataComing["id_template"]
		])){
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "update";
			$arrayResult['RESPONSE'] = "Cannot edit SMS template";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
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