<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','template_name','template_body'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		$id_smsquery = null;
		$conmysql->beginTransaction();
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
				/*$query = $dataComing["query_template_spc_"];
				if(stripos($query,'WHERE') === FALSE){
					if(stripos($query,'GROUP BY') !== FALSE){
						$arrQuery = explode('GROUP BY',$query);
						$query = $arrQuery[0]." WHERE ".$dataComing["condition_target"]." GROUP BY ".$arrQuery[1];
					}else{
						$query .= " WHERE ".$dataComing["condition_target"];
					}
				}else{
					if(stripos($query,'GROUP BY') !== FALSE){
						$arrQuery = explode('GROUP BY',$query);
						$query = $arrQuery[0]." and ".$dataComing["condition_target"]." GROUP BY ".$arrQuery[1];
					}else{
						$query .= " and ".$dataComing["condition_target"];
					}
				}*/
				$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,condition_target,is_bind_param,create_by)
														VALUES(:sms_query,:column_selected,:target_field,:condition_target,'1',:username)");
				if($insertSmsQuery->execute([
					':sms_query' => $dataComing["query_template_spc_"],
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
		$insertTemplate = $conmysql->prepare("INSERT INTO smstemplate(smstemplate_name,smstemplate_body,create_by,id_smsquery) 
												VALUES(:smstemplate_name,:smstemplate_body,:username,:id_smsquery)");
		if($insertTemplate->execute([
			':smstemplate_name' => $dataComing["template_name"],
			':smstemplate_body' => $dataComing["template_body"],
			':username' => $payload["username"],
			':id_smsquery' => $id_smsquery
		])){
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "insert";
			$arrayResult['RESPONSE'] = "Cannot insert SMS template";
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