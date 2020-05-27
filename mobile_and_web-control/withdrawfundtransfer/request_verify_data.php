<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer','bank_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$checkLimitBalance = $conmysql->prepare("SELECT SUM(amount) as sum_amt FROM gctransaction WHERE member_no = :member_no and result_transaction = '1'
													and transaction_type_code = 'WTB' and from_account = :from_account and destination_type = '1'
													and DATE_FORMAT(operate_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')");
		$checkLimitBalance->execute([
			':member_no' => $payload["member_no"],
			':from_account' => $dataComing["deptaccount_no"]
		]);
		$rowBalLimit = $checkLimitBalance->fetch(PDO::FETCH_ASSOC);
		$limit_amt = 0;
		$limit_withdraw = $func->getConstant("limit_withdraw");
		$getDataUser = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE deptaccount_no_bank = :bank_account_no 
											and member_no = :member_no and bindaccount_status = '1'");
		$getDataUser->execute([
			':bank_account_no' => $dataComing["bank_account_no"],
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
		if($balance_request > $limit_withdraw){
			$arrayResult['RESPONSE_CODE'] = "WS0043";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrDataAPI["ToBankAccountNo"] = $dataComing["bank_account_no"];
		$arrDataAPI["FromCoopAccountNo"] = $dataComing["deptaccount_no"];
		$arrDataAPI["TransferAmount"] = $dataComing["amt_transfer"];
		$arrDataAPI["UserRequestDate"] = $dateOperC;
		$arrDataAPI["BankCode"] = $dataComing["bank_code"];
		$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/CheckWithdrawFee",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			$arrSendData = array();
			$arrVerifyToken['exp'] = time() + 60;
			$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
			$arrVerifyToken["operate_date"] = $dateOperC;
			$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
			$arrVerifyToken['deptaccount_no'] = $dataComing["deptaccount_no"];
			$arrVerifyToken['bank_account_no'] = $dataComing["bank_account_no"];
			$verify_token = $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
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
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$arrayResult['PENALTY_AMT'] = $arrResponseAPI->coopFee;
				if((int)preg_replace('/,/', '', $arrResponseAPI->coopFee) > 0){
					$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
					$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
					$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
					$arrayResult['CAUTION'] = $arrayCaution;
				}
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
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0028";
			if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
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