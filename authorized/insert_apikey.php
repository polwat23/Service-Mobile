<?php

require_once('../autoload.php');

if(isset($dataComing['api_key']) && isset($dataComing['unique_id']) && isset($dataComing['channel'])){
	$conmysql_nottest = $con->connecttomysql();
	$checkUNIQUE = $conmysql_nottest->prepare("SELECT id_api FROM gcapikey WHERE unique_id = :unique_id and is_revoke = 0");
	$checkUNIQUE->execute([':unique_id' => $dataComing['unique_id']]);
	if($checkUNIQUE->rowCount() > 0){
		$rowUniq = $checkUNIQUE->fetch();
		$updateAPI = $conmysql_nottest->prepare("UPDATE mdbapikey SET is_revoke = '-9',expire_date = NOW() WHERE id_api = :id_api");
		$conmysql_nottest->beginTransaction();
		if($updateAPI->execute([
			':id_api' => $rowUniq["id_api"]
		])){
			$insertNewAPI = $conmysql_nottest->prepare("INSERT INTO mdbapikey(api_key,unique_id,channel,create_date) VALUES(:api_key,:unique_id,:channel,NOW())");
			if($insertNewAPI->execute([
				':api_key' => $dataComing['api_key'],
				':unique_id' => $dataComing['unique_id'],
				':channel' => $dataComing['channel']
			])){
				$id_api = $conmysql_nottest->lastInsertId();
				$conmysql_nottest->commit();
				$arrayApi = array();
				$arrayApi['ID_API'] = $id_api;
				$arrayApi['RESULT'] = TRUE;
				echo json_encode($arrayApi);
			}else{
				$conmysql_nottest->rollback();
				$arrayError = array();
				$arrayError['RESPONSE_CODE'] = "SQL500";
				$arrayError['RESPONSE'] = "Error! Cannot Insert API";
				$arrayError['RESULT'] = FALSE;
				echo json_encode($arrayError);
			}
		}else{
			$conmysql_nottest->rollback();
			$arrayError = array();
			$arrayError['RESPONSE_CODE'] = "SQL500";
			$arrayError['RESPONSE'] = "Error! Cannot Update Revoke";
			$arrayError['RESULT'] = FALSE;
			echo json_encode($arrayError);
		}
	}else{
		$insertAPI = $conmysql_nottest->prepare("INSERT INTO mdbapikey(api_key,unique_id,channel,create_date) VALUES(:api_key,:unique_id,:channel,NOW())");
		$conmysql_nottest->beginTransaction();
		if($insertAPI->execute([
				':api_key' => $dataComing['api_key'],
				':unique_id' => $dataComing['unique_id'],
				':channel' => $dataComing['channel']
		])){
			$id_api = $conmysql_nottest->lastInsertId();
			$conmysql_nottest->commit();
			$arrayApi = array();
			$arrayApi['ID_API'] = $id_api;
			$arrayApi['RESULT'] = TRUE;
			echo json_encode($arrayApi);
		}else{
			$conmysql_nottest->rollback();
			$arrayError = array();
			$arrayError['RESPONSE_CODE'] = "SQL500";
			$arrayError['RESPONSE'] = $e->getMessage();
			$arrayError['RESULT'] = FALSE;
			echo json_encode($arrayError);
		}
	}
}else{
	$arrayError = array();
	$arrayError['RESPONSE_CODE'] = "PARAM400";
	$arrayError['RESPONSE'] = 'Not complete parameter';
	$arrayError['RESULT'] = FALSE;
	echo json_encode($arrayError);
}
?>