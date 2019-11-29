<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','k_mobile_no','citizen_id','kb_account_no','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$jsonConfigLB = file_get_contents(__DIR__.'/../../json/config_lb_bank.json');
		$configLB = json_decode($jsonConfigLB,true);
		$arrPayload = array();
		$arrPayload["transaction_type"] = "0620";
		$arrPayload["encoding"] = "UTF8";
		$arrPayload["external_system"] = $configLB["external_system"];
		$arrPayload["payee_short_name"] = $configLB["payee_short_name"];
		$arrPayload["payer_short_name"] = $configLB["payer_short_name"];
		$arrPayload["user_mobile_no"] = preg_replace('/-/','',$dataComing["k_mobile_no"]);
		$arrPayload["id"] = $dataComing["citizen_id"];
		$kb_account_no = preg_replace('/-/','',$dataComing["kb_account_no"]);
		$arrPayload["external_reference"] = $member_no.$kb_account_no;
		$arrPayload["auth_parameter"] = $configLB["pass_phrase"];
		$arrPayloadverify = array();
		$arrPayloadverify['member_no'] = $member_no;
		$arrPayloadverify['kb_account_no'] = $kb_account_no;
		$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
		$arrPayloadverify["reference"] = $member_no.$kb_account_no;
		$arrPayloadverify['exp'] = time() + 60;
		$sigma_key = $lib->generate_token();
		$arrPayloadverify['sigma_key'] = $sigma_key;
		$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$arrSendData = array();
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$conmysql->beginTransaction();
		$insertPendingBindAccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,deptaccount_no_bank,bank_code,id_bankpalette,id_token) 
														VALUES(:sigma_key,:member_no,:coop_account_no,:kb_account_no,'004',2,:id_token)");
		if($insertPendingBindAccount->execute([
			':sigma_key' => $sigma_key,
			':member_no' => $member_no,
			':coop_account_no' => $coop_account_no,
			':kb_account_no' => $kb_account_no,
			':id_token' => $payload["id_token"]
		])){
			$responseAPI = $lib->posting_data($configLB["url_api_gensoft"].'/bindaccount/pending_bind_account',$arrSendData);
			if(!$responseAPI){
				$arrayResult['RESPONSE_CODE'] = "WS0017";
				$arrayResult['RESPONSE_MESSAGE'] = "Request to API Bank failed";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$conmysql->commit();
				$arrayResult["URL_CONSENT"] = "https://ws06.uatebpp.kasikornbank.com/PGSRegistration.do";
				$arrayResult['RESULT'] = TRUE;
				//$responsePosting = $lib->posting_data('https://ws04.uatebpp.kasikornbank.com/ws/v1/registerinit',$arrPayload);
				/*if($responsePosting["return_status"] == '0' && $responsePosting["return_code"] == 'K0000'){
					$arrayResult["URL_CONSENT"] = "https://ws06.uatebpp.kasikornbank.com/PGSRegistration.do?reg_id=".$responsePosting["reg_id"]."&langLocale=th_TH";
					$arrayResult['RESULT'] = TRUE;
				}else{
					$arrayResult['RETURN_CODE'] = $responsePosting["return_code"];
					$arrayResult['RETURN_MESSAGE'] = $responsePosting["return_message"];
					$arrayResult['RESULT'] = FALSE;
				}*/
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = $arrResponse->RESPONSE_CODE;
				$arrayResult['RESPONSE_MESSAGE'] = $arrResponse->RESPONSE_MESSAGE;
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1020";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot insert bindaccount to coop server";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			echo json_encode($arrayResult);
			exit();
		}
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