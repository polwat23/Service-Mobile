<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','k_mobile_no','citizen_id','kb_account_no','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$kb_account_no = preg_replace('/-/','',$dataComing["kb_account_no"]);
		$mobile_no = "0867075797";//preg_replace('/-/','',$dataComing["k_mobile_no"]);
		$arrPayloadverify = array();
		$arrPayloadverify['member_no'] = $member_no;
		$arrPayloadverify['user_mobile_no'] = $mobile_no;
		$arrPayloadverify['citizen_id'] = "1341407730121";//$dataComing["citizen_id"];
		$arrPayloadverify['kb_account_no'] = "0011391958";//$kb_account_no;
		$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
		$arrPayloadverify['exp'] = time() + 60;
		$sigma_key = $lib->generate_token();
		$arrPayloadverify['sigma_key'] = $sigma_key;
		$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$arrSendData = array();
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$insertPendingBindAccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,deptaccount_no_bank,mobile_no,bank_code,id_bankpalette,id_token) 
														VALUES(:sigma_key,:member_no,:coop_account_no,:kb_account_no,:mobile_no,'004',2,:id_token)");
		if($insertPendingBindAccount->execute([
			':sigma_key' => $sigma_key,
			':member_no' => $member_no,
			':coop_account_no' => $coop_account_no,
			':kb_account_no' => $kb_account_no,
			':mobile_no' => $mobile_no,
			':id_token' => $payload["id_token"]
		])){
			$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/bindaccount/pending_bind_account',$arrSendData);
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
				$arrayResult["URL_CONSENT"] = $arrResponse->URL_CONSENT;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrResponse->RESPONSE_CODE;
				$arrayResult['RESPONSE_MESSAGE'] = $arrResponse->RESPONSE_MESSAGE;
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
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