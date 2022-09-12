<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conoracle->prepare("SELECT gba.citizen_id,gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.link_withdraw_coopdirect,csb.bank_short_ename
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$arrSendData = array();
		$dateOperC = date('c');
		$ref_no = $time.$lib->randomText('all',3);
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$penalty_include = $func->getConstant("include_penalty");
		$fee_amt = 0;
		if($rowDataWithdraw["BANK_CODE"] == '025'){
			$fee_amt = $dataComing["penalty_amt"];
		}else{
			$fee_amt = $dataComing["penalty_amt"] + $dataComing["fee_amt"];
		}
		if($penalty_include == '0'){
			$amt_transfer = $dataComing["amt_transfer"] - $fee_amt;
		}else{
			$amt_transfer = $dataComing["amt_transfer"];
		}
		$arrVerifyToken['exp'] = $time + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['ref_trans'] = $ref_no;
		$refbank_no = null;
		$etnrefbank_no = null;
		if($rowDataWithdraw["BANK_CODE"] == '004'){
			$arrVerifyToken["tran_id"] = $dataComing["tran_id"];
			$arrVerifyToken["kbank_ref_no"] = $dataComing["kbank_ref_no"];
			$arrVerifyToken['citizen_id_enc'] = $dataComing["citizen_id_enc"];
			$arrVerifyToken['dept_account_enc'] = $dataComing["dept_account_enc"];
			$refbank_no = $dataComing["kbank_ref_no"];
		}else if($rowDataWithdraw["BANK_CODE"] == '006'){
			$arrVerifyToken['tran_date'] = $dateOper;
			$arrVerifyToken['bank_account'] = $rowDataWithdraw["DEPTACCOUNT_NO_BANK"];
			$arrVerifyToken['citizen_id'] = $rowDataWithdraw["CITIZEN_ID"];
		}else if($rowDataWithdraw["BANK_CODE"] == '025'){
			$arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
			$arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
			$refbank_no = $dataComing["SOURCE_REFNO"];
			$etnrefbank_no = $dataComing["ETN_REFNO"];
		}
		
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		// Withdraw Inside --------------------------------------
		$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$fetchDepttype->execute([':deptaccount_no' => $coop_account_no]);
		$rowDataDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC);
		$arrayGroup = array();
		$arrayGroup["account_id"] = $func->getConstant("operative_account");
		$arrayGroup["action_status"] = "1";
		$arrayGroup["atm_no"] = "mobile";
		$arrayGroup["atm_seqno"] = "";
		$arrayGroup["aviable_amt"] = null;
		$arrayGroup["bank_accid"] = $rowDataWithdraw["DEPTACCOUNT_NO_BANK"];
		$arrayGroup["bank_cd"] = $rowDataWithdraw["BANK_CODE"];
		$arrayGroup["branch_cd"] = null;
		$arrayGroup["coop_code"] = $config["COOP_KEY"];
		$arrayGroup["coop_id"] = $config["COOP_ID"];
		$arrayGroup["deptaccount_no"] = $coop_account_no;
		$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
		$arrayGroup["entry_id"] = $dataComing["channel"] == 'mobile_app' ? "MCOOP" : "ICOOP";
		$arrayGroup["fee_amt"] = $fee_amt;
		$arrayGroup["feeinclude_status"] = $penalty_include;
		$arrayGroup["item_amt"] = $amt_transfer;
		$arrayGroup["member_no"] = $member_no;
		$arrayGroup["moneytype_code"] = "CBT";
		$arrayGroup["msg_output"] = null;
		$arrayGroup["msg_status"] = null;
		$arrayGroup["operate_date"] = $dateOperC;
		$arrayGroup["oprate_cd"] = "002";
		$arrayGroup["post_status"] = "1";
		$arrayGroup["principal_amt"] = null;
		$arrayGroup["ref_slipno"] = null;
		$arrayGroup["slipitemtype_code"] = $rowDataWithdraw["ITEMTYPE_WTD"];
		$arrayGroup["stmtitemtype_code"] = $rowDataWithdraw["ITEMTYPE_WTD"];
		$arrayGroup["system_cd"] = "02";
		$arrayGroup["withdrawable_amt"] = null;
		$ref_slipno = null;
		try{
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl",array(
					'keep_alive' => false,
					'connection_timeout' => 900
			));
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
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
					$arrayResult['RESPONSE_MESSAGE'] = $responseSoap->msg_output;//$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$ref_slipno = $responseSoap->ref_slipno;
				$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_sms_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
				$updateSyncNoti->execute([
					':ref_slipno' => $ref_slipno,
					':deptaccount_no' => $coop_account_no
				]);
			}catch(SoapFault $e){
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
					':response_message' => $e->getMessage()
				];
				$log->writeLog('withdrawtrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			// -----------------------------------------------
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["LINK_WITHDRAW_COOPDIRECT"],$arrSendData);
			if(!$responseAPI["RESULT"]){
				$insertTransactionLog = $conoracle->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
															ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,:itemtype,:from_account,'1',:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',TO_DATE(:operate_date,'yyyy/mm/dd hh24:mi:ss'),'-9',SYSDATE,:member_no
															,:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':itemtype' => $rowDataWithdraw["ITEMTYPE_WTD"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataWithdraw["DEPTACCOUNT_NO_BANK"],
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
					':bank_code' => $rowDataWithdraw["BANK_CODE"] ?? '004'
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
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
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no and deptaccount_no = :deptaccount_no");
				$fetchSeqno->execute([
					':deptslip_no' => $ref_slipno,
					':deptaccount_no' => $coop_account_no
				]);
				$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
				if(isset($dataComing["remark"])){
					$id_memo  = $func->getMaxTable('id_memo' , 'gcmemodept');
					$insertRemark = $conoracle->prepare("INSERT INTO gcmemodept(id_memo,memo_text,deptaccount_no,seq_no)
														VALUES(:id_memo,:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':id_memo' =>  $id_memo,
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $coop_account_no,
						':seq_no' => $rowSeqno["SEQ_NO"]
					]);
				}
				
				$arrExecute = [
					':ref_no' => $ref_no,
					':itemtype' => $rowDataWithdraw["ITEMTYPE_WTD"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataWithdraw["DEPTACCOUNT_NO_BANK"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':etn_ref' => $etnrefbank_no,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $refbank_no,
					':bank_code' => $rowDataWithdraw["BANK_CODE"] ?? '004'
				];
				$insertTransactionLog = $conoracle->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,:itemtype,:from_account,'1',:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',TO_DATE(:operate_date,'yyyy/mm/dd hh24:mi:ss'),'1',:member_no,:ref_no1,
															:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
				if($insertTransactionLog->execute($arrExecute)){
				}else{
					$message_error = "ไม่สามารถ Insert ลงตาราง gctransaction ได้"."\n"."Query => ".$insertTransactionLog->queryString."\n".json_encode($arrExecute);
					$lib->sendLineNotify($message_error);
				}
				$arrToken = $func->getFCMToken('person',$payload["member_no"]);
				$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
				$dataMerge = array();
				$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
				$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
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
				$arrayTel = $func->getSMSPerson('person',array($payload["member_no"]));
				foreach($arrayTel as $dest){
					if(isset($dest["TEL"]) && $dest["TEL"] != ""){
						$message_body = $message_endpoint["BODY"];
						$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
						$arraySendSMS = $lib->sendSMS($arrayDest);
						if($arraySendSMS["RESULT"]){
							$arrGRPAll[$dest["MEMBER_NO"]] = $message_body;
							$func->logSMSWasSent(null,$arrGRPAll,$arrayTel,'system',true);
						}else{
							$bulkInsert[] = "(null,'".$message_body."','".$payload["member_no"]."',
									'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','system',null)";
							$func->logSMSWasNotSent($bulkInsert);
						}
					}else{
						$bulkInsert[] = "(null,'".$message_endpoint["BODY"]."','".$payload["member_no"]."',
								'sms','-',null,'ไม่พบเบอร์โทรศัพท์ในระบบ','system',null)";
						$func->logSMSWasNotSent($bulkInsert);
					}
				}
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$insertTransactionLog = $conoracle->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,
															ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,:itemtype,:from_account,'1',:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',TO_DATE(:operate_date,'yyyy/mm/dd hh24:mi:ss'),'-9',SYSDATE,:member_no
															,:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':itemtype' => $rowDataWithdraw["ITEMTYPE_WTD"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataWithdraw["DEPTACCOUNT_NO_BANK"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':etn_ref' => $etnrefbank_no,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $refbank_no,
					':bank_code' => $rowDataWithdraw["BANK_CODE"] ?? '004'
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
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
				if(isset($configError[$rowDataWithdraw["BANK_SHORT_ENAME"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataWithdraw["BANK_SHORT_ENAME"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e) {
			$arrayResult['RESPONSE_CODE'] = "WS0037";
			$message_error = "ไม่สามารถถอนเงินได้สาเหตุเพราะ ".$e->getMessage();
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
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