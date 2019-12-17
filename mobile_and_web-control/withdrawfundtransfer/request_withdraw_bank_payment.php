<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		try{
			$arrSendData = array();
			if(isset($dataComing["sigma_key"])){
				$jsonConfigLB = file_get_contents(__DIR__.'/../../json/config_lb_bank.json');
				$configLB = json_decode($jsonConfigLB,true);
				$arrSendData["remark"] = $dataComing["remark"] ?? null;
				$arrVerifyToken['exp'] = time() + 60;
				$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
				$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
				$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
				$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
				$arrSendData["verify_token"] = $verify_token;
				$arrSendData["app_id"] = $config["APP_ID"];
				$responseAPI = $lib->posting_data($configLB["url_api_gensoft"].'/withdraw/request_withdraw_payment',$arrSendData);
				if(!$responseAPI){
					$arrayResult['RESPONSE_CODE'] = "WS0017";
					$arrayResult['RESPONSE_MESSAGE'] = "Request to API Server failed";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(400);
					echo json_encode($arrayResult);
					exit();
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$arrayResult['EXTERNAL_REF'] = $arrResponse->EXTERNAL_REF;
					$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
					$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
					$arrayResult['RESULT'] = TRUE;
					if(isset($new_token)){
						$arrayResult['NEW_TOKEN'] = $new_token;
					}
					echo json_encode($arrayResult);
				}else{
					$arrayResult['RESPONSE_CODE'] = $arrResponse->RESPONSE_CODE;
					$arrayResult['RESPONSE_MESSAGE'] = $arrResponse->RESPONSE_MESSAGE;
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0020";
				$arrayResult['RESPONSE_MESSAGE'] = "Sigma key is undefinded";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
			$text = date("Y-m-d H:i:s").' > Error : '.json_encode($e);
			file_put_contents(__DIR__.'/../../log/log_withdraw_error.txt', $text . PHP_EOL, FILE_APPEND);
			$arrayResult['RESPONSE_CODE'] = "WS8888";
			$arrayResult['RESPONSE_MESSAGE'] = "Other Exception";
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