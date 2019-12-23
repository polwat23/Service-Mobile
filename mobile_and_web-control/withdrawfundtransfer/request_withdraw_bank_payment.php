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
			$arrSendData["remark"] = $dataComing["remark"] ?? null;
			$arrVerifyToken['exp'] = time() + 60;
			$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
			$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
			$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/withdraw/request_withdraw_payment',$arrSendData);
			if(!$responseAPI){
				$arrayResult['RESPONSE_CODE'] = "WS0030";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถถอนเงินได้ กรุณาติดต่อสหกรณ์ #WS0030";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot withdraw deposit please contact cooperative #WS0030";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$arrayResult['EXTERNAL_REF'] = $arrResponse->EXTERNAL_REF;
				$arrayResult['TRANSACTION_NO'] = $arrResponse->TRANSACTION_NO;
				$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
				$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$text = '#Withdraw Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/withdrawfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = $arrResponse->RESPONSE_CODE;
				$arrayResult['RESPONSE_MESSAGE'] = $arrResponse->RESPONSE_MESSAGE;
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS9999';
			$lib->addLogtoTxt($arrError,'exception_error');
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "เกิดข้อผิดพลาดบางประการกรุณาติดต่อสหกรณ์ #WS9999";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS9999";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>