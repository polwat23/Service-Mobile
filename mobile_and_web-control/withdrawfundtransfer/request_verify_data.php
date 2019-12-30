<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrSendData = array();
		$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
		try {
			$argumentWS = [
							"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
							"as_account_no" => $dataComing["deptaccount_no"],
							"as_itemtype_code" => "WTX",
							"adc_amt" => $dataComing["amt_transfer"],
							"adtm_date" => date('c')
			];
			$resultWS = $clientWS->__call("of_chk_withdrawcount_amt", array($argumentWS));
			$arrayResult['PENALTY_AMT'] = $resultWS->of_chk_withdrawcount_amtResult;
		}catch(SoapFault $e){
			$arrError = array();
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS8002';
			$lib->addLogtoTxt($arrError,'soap_error');
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrVerifyToken['exp'] = time() + 60;
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$fetchCitizenId = $conoracle->prepare("SELECT card_person FROM mbmembmaster WHERE member_no = :member_no");
		$fetchCitizenId->execute([':member_no' => $member_no]);
		$rowCitizen = $fetchCitizenId->fetch();
		$arrVerifyToken['citizen_id'] = $rowCitizen["CARD_PERSON"];
		$arrVerifyToken['deptaccount_no'] = $dataComing["deptaccount_no"];
		$arrVerifyToken['bank_account_no'] = preg_replace('/-/','',$dataComing["bank_account_no"]);
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/verifydata/request_verify_data',$arrSendData);
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
			$text = '#Verify Data withdraw Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
			file_put_contents(__DIR__.'/../../log/verifydata_error.txt', $text . PHP_EOL, FILE_APPEND);
			$arrayResult['RESPONSE_CODE'] = "WS0042";
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