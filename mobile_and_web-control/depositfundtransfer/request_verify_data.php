<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		try {
			$arrSendData = array();
			$arrVerifyToken['exp'] = time() + 60;
			$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
			$fetchCitizenId = $conoracle->prepare("SELECT card_person FROM mbmembmaster WHERE member_no = :member_no");
			$fetchCitizenId->execute([':member_no' => $payload["member_no"]]);
			$rowCitizen = $fetchCitizenId->fetch();
			$arrVerifyToken['citizen_id'] = $rowCitizen["CARD_PERSON"] ?? "1500900999999";
			$arrVerifyToken['bank_account_no'] = preg_replace('/-/','',$dataComing["bank_account_no"]);
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/verifydata/kbank/request_verify_data',$arrSendData);
			if(!$responseAPI){
				$arrayResult['RESPONSE_CODE'] = "WS0028";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$arrayResult['FEE_AMT'] = 0;
				$arrayResult['ACCOUNT_NAME'] = $arrResponse->ACCOUNT_NAME;
				$arrayResult['REF_KBANK'] = $arrResponse->REF_KBANK;
				$arrayResult['CITIZEN_ID_ENC'] = $arrResponse->CITIZEN_ID_ENC;
				$arrayResult['BANK_ACCOUNT_ENC'] = $arrResponse->BANK_ACCOUNT_ENC;
				$arrayResult['TRAN_ID'] = $arrResponse->TRAN_ID;
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$text = '#Verify Data Deposit Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/verifydata_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0042";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS9999';
			$lib->addLogtoTxt($arrError,'exception_error');
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
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