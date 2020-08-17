<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','kbank_ref_no','amt_transfer','citizen_id_enc',
'dept_account_enc','tran_id','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$arrSendData = array();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$penalty_include = $func->getConstant("include_penalty");
		if($penalty_include == '0'){
			$amt_transfer = $dataComing["amt_transfer"] - $dataComing["penalty_amt"] - $dataComing["fee_amt"];
		}else{
			$amt_transfer = $dataComing["amt_transfer"] - $dataComing["fee_amt"];
		}
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken["tran_id"] = $dataComing["tran_id"];
		$arrVerifyToken["kbank_ref_no"] = $dataComing["kbank_ref_no"];
		$arrVerifyToken['citizen_id_enc'] = $dataComing["citizen_id_enc"];
		$arrVerifyToken['dept_account_enc'] = $dataComing["dept_account_enc"];
		$verify_token = $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		// Withdraw Inside --------------------------------------
		$fetchDataDeposit = $conmysql->prepare("SELECT bank_code,deptaccount_no_bank FROM gcbindaccount WHERE sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$ref_slipno = null;
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrDataAPI["FromCoopAccountNo"] = $coop_account_no;
		$arrDataAPI["ToBankAccountNo"] = $rowDataDeposit["deptaccount_no_bank"];
		$arrDataAPI["WithdrawAmount"] = $amt_transfer;
		$arrDataAPI["UserRequestDate"] = $dateOperC;
		$arrDataAPI["Note"] = "Withdraw Bank";
		$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/WithdrawFromCoopAccount",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode != "200"){
			$arrayResult['RESPONSE_CODE'] = "WS0041";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $arrResponseAPI->responseMessage
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$ref_slipno = $arrResponseAPI->trxID;
		// -----------------------------------------------
		$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/withdrawdeposit_kbank',$arrSendData);
		if(!$responseAPI["RESULT"]){
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
														ref_no_1,coop_slip_no,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,'WTB',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'-9',NOW(),:member_no
														,:ref_no1,:slip_no,:id_userlogin,:ref_no_source,:bank_code)");
			$insertTransactionLog->execute([
				':ref_no' => $dataComing["tran_id"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataDeposit["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $dataComing["kbank_ref_no"],
				':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
			]);
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["TrxId"] = $ref_slipno;
			$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/ReverseWithDraw",$arrDataAPI,$arrHeaderAPI);
			$arrayResult['RESPONSE_CODE'] = "WS0030";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
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
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["TrxId"] = $ref_slipno;
			$arrDataAPI["BankTransferRefCode"] = $dataComing["tran_id"];
			$arrDataAPI["BankTransferRefDate"] = $dateOperC;
			$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/SaveWithdrawRefCodeFromBankAccount",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,ref_no)
												VALUES(:remark,:deptaccount_no,:seq_no)");
			$insertRemark->execute([
				':remark' => $dataComing["remark"],
				':deptaccount_no' => $coop_account_no,
				':seq_no' => $ref_slipno
			]);
			$arrExecute = [
				':ref_no' => $dataComing["tran_id"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataDeposit["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $dataComing["kbank_ref_no"],
				':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
			];
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
														ref_no_1,coop_slip_no,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,'WTB',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'1',:member_no,:ref_no1,
														:slip_no,:id_userlogin,:ref_no_source,:bank_code)");
			if($insertTransactionLog->execute($arrExecute)){
			}else{
				$arrLogTemp = array();
				$arrLogTemp["DATA"] = $arrExecute;
				$arrLogTemp["QUERY"] = $insertTransactionLog;
				$lib->addLogtoTxt($arrLogTemp,'log_withdraw_transaction_temp');
			}
			/*$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
			$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
			foreach($arrToken["LIST_SEND"] as $dest){
				$dataMerge = array();
				$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
				$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
				$dataMerge["DATETIME"] = $lib->convertdate($dateOper,'D m Y',true);
				$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
				$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
				$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
				$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
				$arrMessage["BODY"] = $message_endpoint["BODY"];
				$arrMessage["PATH_IMAGE"] = null;
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
				if($func->insertHistory($arrPayloadNotify,'2')){
					$lib->sendNotify($arrPayloadNotify,"person");
				}
			}*/
			$arrayResult['TRANSACTION_NO'] = $dataComing["tran_id"];
			$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
														ref_no_1,coop_slip_no,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,'WTB',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'-9',NOW(),:member_no
														,:ref_no1,:slip_no,:id_userlogin,:ref_no_source,:bank_code)");
			$insertTransactionLog->execute([
				':ref_no' => $dataComing["tran_id"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataDeposit["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $dataComing["kbank_ref_no"],
				':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
			]);
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["TrxId"] = $ref_slipno;
			$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."Account/ReverseWithDraw",$arrDataAPI,$arrHeaderAPI);
			$arrayResult['RESPONSE_CODE'] = "WS0037";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
				':response_code' => $arrayResult['RESPONSE_CODE'],
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