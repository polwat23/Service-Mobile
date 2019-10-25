<?php
require_once('../autoloadConnection.php');

if(isset($dataComing['api_key']) && isset($dataComing['channel']) && isset($dataComing['unique_id']) && isset($dataComing['id_api_source'])){
	$conmysql_nottest = $con->connecttomysql();
	$conmysql_nottest->beginTransaction();
	if($dataComing['channel'] == 'mobile_app'){
		$expire_date = NULL;
	}else{
		$expire_date = date('Y-m-d H:i:s',strtotime("+1 day"));
	}
	$insertAPI = $conmysql_nottest->prepare("INSERT INTO gcapikey(api_key,expire_date,id_api_source,unique_id) 
											VALUES(:api_key,:expire_date,:id_api_source,:unique_id)");
	if($insertAPI->execute([
			':api_key' => $dataComing['api_key'],
			':expire_date' => $expire_date,
			':id_api_source' => $dataComing['id_api_source'],
			':unique_id' => $dataComing['unique_id']
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
}else{
	$arrayError = array();
	$arrayError['RESPONSE_CODE'] = "PARAM400";
	$arrayError['RESPONSE'] = 'Not complete parameter';
	$arrayError['RESULT'] = FALSE;
	echo json_encode($arrayError);
}
?>