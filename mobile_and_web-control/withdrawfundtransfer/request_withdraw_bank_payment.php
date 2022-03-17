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
		try{
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			$fetchRecpPaytype = $conoracle->prepare("SELECT default_accid FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype_dep");
			$fetchRecpPaytype->execute([':itemtype_dep' => $rowDataWithdraw["itemtype_wtd"]]);
			$rowAccid = $fetchRecpPaytype->fetch(PDO::FETCH_ASSOC);
			$dateOperC = date('c');
			$constantDep = $cal_dep->getConstantAcc($coop_account_no);
			$arrayGroup = array();
			$arrayGroup["account_id"] = $rowAccid["DEFAULT_ACCID"];
			$arrayGroup["action_status"] = "1";
			$arrayGroup["atm_no"] = "MOBILE";
			$arrayGroup["atm_seqno"] = null;
			$arrayGroup["aviable_amt"] = null;
			$arrayGroup["bank_accid"] = null;
			$arrayGroup["bank_cd"] = '025';
			$arrayGroup["branch_cd"] = null;
			$arrayGroup["coop_code"] = $config["COOP_KEY"];
			$arrayGroup["coop_id"] = "065001";
			$arrayGroup["deptaccount_no"] = $coop_account_no;
			$arrayGroup["depttype_code"] = $constantDep["DEPTTYPE_CODE"];
			$arrayGroup["entry_id"] = "MOBILE";
			$arrayGroup["fee_amt"] = $dataComing["fee_amt"] ?? 0;
			$arrayGroup["fee_operate_cd"] = '0';
			$arrayGroup["feeinclude_status"] = '1';
			$arrayGroup["item_amt"] = $dataComing["amt_transfer"];
			$arrayGroup["member_no"] = $member_no;
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = $dateOperC;
			$arrayGroup["oprate_cd"] = "002";
			$arrayGroup["post_status"] = "1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_app"] = "MOBILE";
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = $rowDataWithdraw["itemtype_wtd"];
			$arrayGroup["stmtitemtype_code"] = $rowDataWithdraw["itemtype_wtd"];
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			$argumentWS = [
				"as_wspass" => $config["WS_PASS"],
				"astr_dept_inf_serv" => $arrayGroup
			];
			$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
			$responseSoap = $resultWS->of_dept_inf_servResult;
			if($responseSoap->msg_status != '0000'){
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
					':response_message' => $responseSoap->msg_output
				];
				$log->writeLog('withdrawtrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}

			if($responseSoap->msg_status == '0000'){
				$ref_slipno = $responseSoap->ref_slipno;
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
					$arrayGroup["post_status"] = "-1";
					$arrayGroup["atm_no"] = $ref_slipno;
					$argumentWS = [
							"as_wspass" => $config["WS_PASS"],
							"astr_dept_inf_serv" => $arrayGroup
					];
					$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
					$responseSoapCancel = $resultWS->of_dept_inf_servResult;
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
					$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
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
					$arrayGroup["post_status"] = "-1";
					$arrayGroup["atm_no"] = $ref_slipno;
					$argumentWS = [
							"as_wspass" => $config["WS_PASS"],
							"astr_dept_inf_serv" => $arrayGroup
					];
					$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
					$responseSoapCancel = $resultWS->of_dept_inf_servResult;
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
				
			}
		}catch(SoapFault $e){
			file_put_contents('request_ErrWTD.txt', json_encode($e,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
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
				':response_message' => 'ถอนไปยังธนาคาร '.$rowDataWithdraw["bank_short_ename"].' '.json_encode($e,JSON_UNESCAPED_UNICODE)
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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