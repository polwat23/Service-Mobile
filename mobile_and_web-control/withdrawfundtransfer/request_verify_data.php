<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$checkLimitBalance = $conmysql->prepare("SELECT SUM(amount) as sum_amt FROM gctransaction WHERE member_no = :member_no and result_transaction = '1'
													and transaction_type_code = 'WTX' and from_account = :from_account and destination_type = '1'
													and DATE_FORMAT(operate_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')");
		$checkLimitBalance->execute([
			':member_no' => $payload["member_no"],
			':from_account' => $dataComing["deptaccount_no"]
		]);
		$rowBalLimit = $checkLimitBalance->fetch(PDO::FETCH_ASSOC);
		$limit_amt = 0;
		$limit_withdraw = $func->getConstant("limit_withdraw");
		$getDataUser = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE deptaccount_no_coop = :deptaccount_no 
											and member_no = :member_no and bindaccount_status = '1'");
		$getDataUser->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"],
			':member_no' => $payload["member_no"]
		]);
		$rowDataUser = $getDataUser->fetch(PDO::FETCH_ASSOC);
		$fetchLimitTransaction = $conmysql->prepare("SELECT limit_transaction_amt FROM gcuserallowacctransaction 
														WHERE member_no = :member_no and deptaccount_no = :deptaccount_no");
		$fetchLimitTransaction->execute([
			':member_no' => $payload["member_no"],
			':deptaccount_no' => $dataComing["deptaccount_no"]
		]);
		$rowLimitTransaction = $fetchLimitTransaction->fetch(PDO::FETCH_ASSOC);
		if($limit_withdraw >= $rowLimitTransaction["limit_transaction_amt"]){
			$limit_amt = (int)$rowLimitTransaction["limit_transaction_amt"];
		}else{
			$limit_amt = (int)$limit_withdraw;
		}
		$balance_request = $rowBalLimit["sum_amt"] + $dataComing["amt_transfer"];
		if($balance_request > $limit_amt){
			$arrayResult['RESPONSE_CODE'] = "WS0043";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrSendData = array();
		$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
		try {
			$argumentWS = [
				"as_wspass" => $config["WS_STRC_DB"],
				"as_account_no" => $dataComing["deptaccount_no"],
				"as_itemtype_code" => "WTX",
				"adc_amt" => $dataComing["amt_transfer"],
				"adtm_date" => date('c')
			];
			$resultWS = $clientWS->__call("of_chk_withdrawcount_amt", array($argumentWS));
			$arrayResult['PENALTY_AMT'] = $resultWS->of_chk_withdrawcount_amtResult;
		}catch(SoapFault $e){
			$arrayResult["RESPONSE_CODE"] = 'WS8002';
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $e->getMessage()
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrVerifyToken['exp'] = time() + 60;
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
		$arrVerifyToken['deptaccount_no'] = $dataComing["deptaccount_no"];
		$arrVerifyToken['bank_account_no'] = preg_replace('/-/','',$dataComing["bank_account_no"]);
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/verifydata_kbank',$arrSendData);
		if(!$responseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS0028";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $responseAPI["RESPONSE_MESSAGE"]
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponse = json_decode($responseAPI);
		if($arrResponse->RESULT){
			$arrayResult['FEE_AMT'] = 0;
			$arrayResult['ACCOUNT_NAME'] = $arrResponse->ACCOUNT_NAME;
			$arrayResult['ACCOUNT_NAME_EN'] = $arrResponse->ACCOUNT_NAME_EN;
			$arrayResult['REF_KBANK'] = $arrResponse->REF_KBANK;
			$arrayResult['CITIZEN_ID_ENC'] = $arrResponse->CITIZEN_ID_ENC;
			$arrayResult['BANK_ACCOUNT_ENC'] = $arrResponse->BANK_ACCOUNT_ENC;
			$arrayResult['TRAN_ID'] = $arrResponse->TRAN_ID;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0042";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $arrayResult["RESPONSE_MESSAGE"]
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
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