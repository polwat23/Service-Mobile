<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component','k_mobile_no','citizen_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrPayload = array();
		$arrPayload["transaction_type"] = "0620";
		$arrPayload["encoding"] = "UTF8";
		$arrPayload["external_system"] = "ISOCAREETN";
		$arrPayload["payee_short_name"] = "MAHIDOLPAYEE";
		$arrPayload["payer_short_name"] = "MAHIDOLPAYER";
		$arrPayload["user_mobile_no"] = preg_replace('/-/','',$dataComing["k_mobile_no"]);
		$arrPayload["id"] = $dataComing["citizen_id"];
		$arrPayload["external_reference"] = "GensoftCoopDirect";
		$arrPayload["auth_parameter"] = "GensoftCoopDirect";
		//$responsePosting = $lib->posting_data('https://ws04.uatebpp.kasikornbank.com/ws/v1/registerinit',$arrPayload);
		if($responsePosting["return_status"] == '0' && $responsePosting["return_code"] == 'K0000'){
			$arrayResult["URL_CONSENT"] = "https://ws06.uatebpp.kasikornbank.com/PGSRegistration.do?reg_id=".$responsePosting["reg_id"]."&langLocale=th_TH";
			$arrayResult['RESULT'] = TRUE;
		}else{
			$arrayResult['RETURN_CODE'] = $responsePosting["return_code"];
			$arrayResult['RETURN_MESSAGE'] = $responsePosting["return_message"];
			$arrayResult['RESULT'] = FALSE;
		}
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		echo json_encode($arrayResult);
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