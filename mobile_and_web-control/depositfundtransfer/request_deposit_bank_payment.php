<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$cert = __DIR__."/../api.gensoft.co.th-crt.cer";
		$key = __DIR__."/../_.gensoft.co.th-key.pem";
		$arrSendData = array();
		$responseAPI = $lib->posting_data('https://58.137.161.157:5443/OnlineDirectWebService/services/FundTransferService?wsdl',$arrSendData,$cert,$key);
		if(!$responseAPI){
			$arrayResult['RESPONSE_CODE'] = "WS0017";
			$arrayResult['RESPONSE_MESSAGE'] = "Request to API Server failed";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponse = json_decode($responseAPI);
		echo json_encode($arrResponse);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>