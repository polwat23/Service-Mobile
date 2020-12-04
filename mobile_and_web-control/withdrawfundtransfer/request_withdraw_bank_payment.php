<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_dep,csb.link_deposit_coopdirect,csb.bank_short_ename
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$flag_transaction_coop = false;
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
		if($rowDataDeposit["bank_code"] == '004'){
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
		}else if($rowDataDeposit["bank_code"] == '006'){
			
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
		$arrayGroup["atm_seqno"] = null;
		$arrayGroup["aviable_amt"] = null;
		$arrayGroup["bank_accid"] = $rowDataDeposit["deptaccount_no_bank"];
		$arrayGroup["bank_cd"] = $rowDataDeposit["bank_code"];
		$arrayGroup["branch_cd"] = null;
		$arrayGroup["coop_code"] = $config["COOP_KEY"];
		$arrayGroup["coop_id"] = $config["COOP_ID"];
		$arrayGroup["deptaccount_no"] = $coop_account_no;
		$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
		$arrayGroup["entry_id"] = "KBANK";
		$arrayGroup["fee_amt"] = $dataComing["penalty_amt"];
		$arrayGroup["feeinclude_status"] = $penalty_include;
		$arrayGroup["item_amt"] = $amt_transfer;
		$arrayGroup["member_no"] = $member_no;
		$arrayGroup["moneytype_code"] = "CBT";
		$arrayGroup["msg_output"] = null;
		$arrayGroup["msg_status"] = null;
		$arrayGroup["operate_date"] = $dateOperC;
		$arrayGroup["oprate_cd"] = "003";
		$arrayGroup["post_status"] = "1";
		$arrayGroup["principal_amt"] = null;
		$arrayGroup["ref_slipno"] = null;
		$arrayGroup["slipitemtype_code"] = "WTB";
		$arrayGroup["stmtitemtype_code"] = "DTB";
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
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_serv_cenResult;
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
				$ref_slipno = $responseSoap->ref_slipno;
				$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
				$updateSyncNoti->execute([
					':ref_slipno' => $ref_slipno,
					':deptaccount_no' => $coop_account_no
				]);
				$flag_transaction_coop = true;
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
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
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
				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
													VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $coop_account_no,
					':seq_no' => $rowSeqno["SEQ_NO"]
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
					$message_error = "ไม่สามารถ Insert ลงตาราง gctransaction ได้"."\n"."Query => ".$insertTransactionLog->queryString."\n".json_encode($arrExecute);
					$lib->sendLineNotify($message_error);
				}
				$arrToken = $func->getFCMToken('person',$payload["member_no"]);
				$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
				foreach($arrToken["LIST_SEND"] as $dest){
					if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
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
						$arrPayloadNotify["SEND_BY"] = "system";
						if($lib->sendNotify($arrPayloadNotify,"person")){
							$func->insertHistory($arrPayloadNotify,'2');
						}
					}
				}
				$arrayResult['TRANSACTION_NO'] = $dataComing["tran_id"];
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
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
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
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
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e) {
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