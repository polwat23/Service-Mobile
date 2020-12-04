<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$min_amount_deposit = $func->getConstant("min_amount_deposit");
		$limit_withdraw_in_day = $func->getConstant("limit_withdraw_in_day");
		if($dataComing["amt_transfer"] < (int) $min_amount_deposit){
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($min_amount_deposit,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$limit_amt = 0;
		$limit_withdraw = $func->getConstant("limit_amount_transaction");
		$getDataUser = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE deptaccount_no_coop = :deptaccount_no 
											and member_no = :member_no and bindaccount_status = '1'");
		$getDataUser->execute([
			':deptaccount_no' => $dataComing["deptaccount_no"],
			':member_no' => $payload["member_no"]
		]);
		$rowDataUser = $getDataUser->fetch(PDO::FETCH_ASSOC);
		$fetchLimitTransaction = $conmysql->prepare("SELECT limit_amount_transaction FROM gcmemberaccount 
														WHERE member_no = :member_no");
		$fetchLimitTransaction->execute([
			':member_no' => $payload["member_no"]
		]);
		$rowLimitTransaction = $fetchLimitTransaction->fetch(PDO::FETCH_ASSOC);
		if($limit_withdraw >= $rowLimitTransaction["limit_amount_transaction"]){
			$limit_amt = (int)$rowLimitTransaction["limit_amount_transaction"];
		}else{
			$limit_amt = (int)$limit_withdraw;
		}
		if($dataComing["amt_transfer"] > $limit_amt){
			$arrayResult['RESPONSE_CODE'] = "WS0043";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$getLimitPerDay = $conmysql->prepare("SELECT SUM(amount) AS all_amt_in_day FROM gctransaction WHERE result_transaction = '1' and trans_flag = '-1' and destination_type = '1'
																and member_no = :member_no and DATE_FORMAT(operate_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')");
		$getLimitPerDay->execute([':member_no' => $payload["member_no"]]);
		$rowLimitPerDay = $getLimitPerDay->fetch(PDO::FETCH_ASSOC);
		$limitPerDay = $rowLimitPerDay["all_amt_in_day"] + $dataComing["amt_transfer"];
		if($limitPerDay > $limit_withdraw_in_day){
			$arrayResult['RESPONSE_CODE'] = "WS0043";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrSendData = array();
		/*$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
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
				':amt_transfer' => $dataComing["amt_transfer"],
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $e->getMessage()
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}*/
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken["operate_date"] =  $dateOperC;
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
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponse = json_decode($responseAPI);
		if($arrResponse->RESULT){
			$arrayResult['PENALTY_AMT']  = 0;
			$arrayResult['FEE_AMT'] = 0;
			$arrayResult['ACCOUNT_NAME'] = $arrResponse->ACCOUNT_NAME;
			$arrayResult['ACCOUNT_NAME_EN'] = $arrResponse->ACCOUNT_NAME_EN;
			$arrayResult['REF_KBANK'] = $arrResponse->REF_KBANK;
			$arrayResult['CITIZEN_ID_ENC'] = $arrResponse->CITIZEN_ID_ENC;
			$arrayResult['BANK_ACCOUNT_ENC'] = $arrResponse->BANK_ACCOUNT_ENC;
			$arrayResult['TRAN_ID'] = $arrResponse->TRAN_ID;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] =  'WS0042';
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':response_code' => $arrResponse->RESPONSE_CODE,
				':response_message' => $arrResponse->RESPONSE_MESSAGE
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			if(isset($configError["KBANK_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["KBANK_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>