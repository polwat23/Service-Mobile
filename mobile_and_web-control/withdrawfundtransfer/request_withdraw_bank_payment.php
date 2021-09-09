<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.link_withdraw_coopdirect,csb.bank_short_ename
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$arrSendData = array();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$ref_no = time().$lib->randomText('all',3);
		$penalty_include = $func->getConstant("include_penalty");
		if($rowDataWithdraw["bank_code"] == '025'){
			$fee_amt = $dataComing["penalty_amt"];
		}else{
			$fee_amt = $dataComing["penalty_amt"] + $dataComing["fee_amt"];
		}
		$amt_transfer = $dataComing["amt_transfer"];
		$arrVerifyToken['exp'] = $time + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['ref_trans'] = $ref_no;
		$refbank_no = null;
		$etnrefbank_no = null;
		if($rowDataWithdraw["bank_code"] == '004'){
			$arrVerifyToken["tran_id"] = $dataComing["tran_id"];
			$arrVerifyToken["kbank_ref_no"] = $dataComing["kbank_ref_no"];
			$arrVerifyToken['citizen_id_enc'] = $dataComing["citizen_id_enc"];
			$arrVerifyToken['dept_account_enc'] = $dataComing["dept_account_enc"];
			$refbank_no = $dataComing["kbank_ref_no"];
		}else if($rowDataWithdraw["bank_code"] == '006'){
			$arrVerifyToken['tran_date'] = $dateOper;
			$arrVerifyToken['bank_account'] = $rowDataWithdraw["deptaccount_no_bank"];
			$arrVerifyToken['citizen_id'] = $rowDataWithdraw["citizen_id"];
		}else if($rowDataWithdraw["bank_code"] == '025'){
			$arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
			$arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
			$refbank_no = $dataComing["SOURCE_REFNO"];
			$etnrefbank_no = $dataComing["ETN_REFNO"];
		}else if($rowDataWithdraw["bank_code"] == '014'){
			$arrVerifyToken['transaction_time'] = $dataComing["TRAN_TIME"];
			$arrVerifyToken['token_id'] = $dataComing["TOKEN_ID"];
			$arrVerifyToken['tran_uniq'] = $dataComing["TRAN_UNIQ"];
		}
		$verify_token = $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		// Withdraw Inside --------------------------------------
		$ref_slipno = null;
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrDataAPI["FromCoopAccountNo"] = $coop_account_no;
		$arrDataAPI["ToBankAccountNo"] = $rowDataWithdraw["deptaccount_no_bank"];
		$arrDataAPI["ToBankCode"] = $rowDataWithdraw["bank_code"];
		$arrDataAPI["WithdrawAmount"] = $amt_transfer;
		$arrDataAPI["TransferFee"] = $dataComing["fee_amt"];
		$arrDataAPI["UserRequestDate"] = $dateOperC;
		$arrDataAPI["Note"] = "Withdraw ".$rowDataWithdraw["bank_short_ename"];
		$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/WithdrawFromCoopAccount",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/WithdrawFromCoopAccount",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/WithdrawFromCoopAccount";
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
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
			require_once('../../include/exit_footer.php');
			
		}
		$ref_slipno = $arrResponseAPI->trxID;
		// -----------------------------------------------
		$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_withdraw_coopdirect"],$arrSendData);
		if(!$responseAPI["RESULT"]){
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
														ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'-9',NOW(),:member_no
														,:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':itemtype' => $rowDataWithdraw["itemtype_wtd"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataWithdraw["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':etn_ref' => $etnrefbank_no,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $refbank_no,
				':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
			]);
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["TrxId"] = $ref_slipno;
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/ReverseWithDraw",$arrDataAPI,$arrHeaderAPI);
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
			$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"];
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponse = json_decode($responseAPI);
		if($arrResponse->RESULT){
			if($rowDataWithdraw["bank_code"] == '004'){
				$refno_source = $dataComing["kbank_ref_no"];
				$etn_refno = $dataComing["tran_id"];
			}else if($rowDataWithdraw["bank_code"] == '006'){
				$refno_source = $arrResponse->KTB_REF;
				$etn_refno = $arrResponse->TRANSACTION_NO;
			}
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["TrxId"] = $ref_slipno;
			$arrDataAPI["BankTransferRefCode"] = $etn_refno;
			$arrDataAPI["BankTransferRefDate"] = $dateOperC;
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/SaveWithdrawRefCodeFromBankAccount",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS9999",
					":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/SaveWithdrawRefCodeFromBankAccount",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/SaveWithdrawRefCodeFromBankAccount";
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,ref_no)
												VALUES(:remark,:deptaccount_no,:seq_no)");
			$insertRemark->execute([
				':remark' => $dataComing["remark"],
				':deptaccount_no' => $coop_account_no,
				':seq_no' => $ref_slipno
			]);
			$arrExecute = [
				':ref_no' => $ref_no,
				':itemtype' => $rowDataWithdraw["itemtype_wtd"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataWithdraw["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':etn_refno' => $etn_refno,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $refno_source,
				':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
			];
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
														ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'1',:member_no,:ref_no1,
														:slip_no,:etn_refno,:id_userlogin,:ref_no_source,:bank_code)");
			if($insertTransactionLog->execute($arrExecute)){
			}else{
				$message_error = "ไม่สามารถ Insert ลงตาราง gctransaction ได้"."\n"."Query => ".$insertTransactionLog->queryString."\n".json_encode($arrExecute);
				$lib->sendLineNotify($message_error);
			}
			$arrToken = $func->getFCMToken('person',$payload["member_no"]);
			$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
			$dataMerge = array();
			$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
			$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
			$dataMerge["DATETIME"] = $lib->convertdate($dateOper,'D m Y',true);
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			foreach($arrToken["LIST_SEND"] as $dest){
				if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
					$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
					$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
					$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
					$arrMessage["BODY"] = $message_endpoint["BODY"];
					$arrMessage["PATH_IMAGE"] = null;
					$arrPayloadNotify["PAYLOAD"] = $arrMessage;
					$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
					$arrPayloadNotify["SEND_BY"] = "system";
					$arrPayloadNotify["TYPE_NOTIFY"] = "2";
					if($lib->sendNotify($arrPayloadNotify,"person")){
						$func->insertHistory($arrPayloadNotify,'2');
					}
				}
			}
			foreach($arrToken["LIST_SEND_HW"] as $dest){
				if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
					$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
					$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
					$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
					$arrMessage["BODY"] = $message_endpoint["BODY"];
					$arrMessage["PATH_IMAGE"] = null;
					$arrPayloadNotify["PAYLOAD"] = $arrMessage;
					$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
					$arrPayloadNotify["SEND_BY"] = "system";
					$arrPayloadNotify["TYPE_NOTIFY"] = "2";
					if($lib->sendNotifyHW($arrPayloadNotify,"person")){
						$func->insertHistory($arrPayloadNotify,'2');
					}
				}
			}
			$arrayResult['TRANSACTION_NO'] = $ref_no;
			$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
														ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
														VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'-9',NOW(),:member_no
														,:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':itemtype' => $rowDataWithdraw["itemtype_wtd"],
				':from_account' => $coop_account_no,
				':destination' => $rowDataWithdraw["deptaccount_no_bank"],
				':amount' => $dataComing["amt_transfer"],
				':fee_amt' => $dataComing["fee_amt"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amt_transfer,
				':oper_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':ref_no1' => $coop_account_no,
				':slip_no' => $ref_slipno,
				':etn_ref' => $etnrefbank_no,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $refbank_no,
				':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
			]);
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["TrxId"] = $ref_slipno;
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/ReverseWithDraw",$arrDataAPI,$arrHeaderAPI);
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
			if(isset($configError[$rowDataWithdraw["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataWithdraw["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
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