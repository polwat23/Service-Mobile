<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
		$structureReqLoan = array();
		$structureReqLoan["coop_id"] = $config["COOP_ID"];
		$structureReqLoan["member_no"] = $member_no;
		$structureReqLoan["loantype_code"] = '1010';
		$structureReqLoan["operate_date"] = date("c");
		try {
			$argumentWS = [
				"as_wspass" => $config["WS_STRC_DB"],
				"atr_lnatm" => $structureReqLoan
			];
			$resultWS = $clientWS->__call("of_initloanrequest_mobile_atm", array($argumentWS));
			$Retutn = $resultWS->atr_lnatm;
			print_r($Retutn);
		}catch(SoapFault $e){
			print_r($e);
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>