<?php
/*ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');*/
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/validate_input.php');
use Connection\connection;
use Utility\library;
$con = new connection();
$conmysql = $con->connecttomysql();

if(isset($dataComing['api_key']) && isset($dataComing['channel']) && isset($dataComing['unique_id']) && isset($dataComing['id_api_source'])){
	$conmysql->beginTransaction();
	if($dataComing['channel'] == 'mobile_app'){
		$expire_date = NULL;
	}else{
		$expire_date = date('Y-m-d H:i:s',strtotime("+1 day"));
	}
	$insertAPI = $conmysql->prepare("INSERT INTO gcapikey(api_key,expire_date,id_api_source,unique_id) 
											VALUES(:api_key,:expire_date,:id_api_source,:unique_id)");
	if($insertAPI->execute([
		':api_key' => $dataComing['api_key'],
		':expire_date' => $expire_date,
		':id_api_source' => $dataComing['id_api_source'],
		':unique_id' => $dataComing['unique_id']
	])){
		$id_api = $conmysql->lastInsertId();
		$conmysql->commit();
		$arrayApi = array();
		$arrayApi['ID_API'] = $id_api;
		$arrayApi['RESULT'] = TRUE;
		echo json_encode($arrayApi);
	}else{
		$conmysql->rollback();
		$arrayError = array();
		$arrayError['RESPONSE_CODE'] = "SQL500";
		$arrayError['RESPONSE'] = "Cannot insert API Key";
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