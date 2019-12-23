<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','template_name','template_body','id_template'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate',$conmysql)){
		$id_smsquery = null;
		$conmysql->beginTransaction();
		if(isset($dataComing["query_template"]) && isset($dataComing["column_selected"])){
			$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_in_query,username)
													VALUES(:sms_query,:column_selected,:username)");
			if($insertSmsQuery->execute([
				':sms_query' => $dataComing["query_template"],
				':column_selected' => $dataComing["column_selected"],
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