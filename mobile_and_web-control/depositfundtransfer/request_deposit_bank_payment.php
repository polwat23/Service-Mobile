<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$amt_transfer = $dataComing["amt_transfer"] - $dataComing["fee_amt"];
		$arrSendData = array();
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		// Deposit Inside --------------------------------------
		$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/depositfundtransfer_kbank',$arrSendData);
		if(!$responseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$arrayResult['RESPONSE_CODE'] = "WS0027";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':sigma_key' => $dataComing["sigma_key"],
				':amt_transfer' => $amt_transfer,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้",
				':is_adj' => '0',
				':ref_no' => null
			];
			$log->writeLog('deposittrans',$arrayStruc);
			$message_error = "เมนูถูกปิดเพราะ ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponse = json_decode($responseAPI);
		if($arrResponse->RESULT){
			$transaction_no = $arrResponse->TRANSACTION_NO;
			$etn_ref = $arrResponse->EXTERNAL_REF;
			$fetchDataDeposit = $conmysql->prepare("SELECT bank_code,deptaccount_no_bank FROM gcbindaccount WHERE sigma_key = :sigma_key");
			$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
			$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
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
			$arrayGroup["fee_amt"] = "0";
			$arrayGroup["feeinclude_status"] = "1";
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
			$arrayGroup["slipitemtype_code"] = "DTB";
			$arrayGroup["stmtitemtype_code"] = "WTB";
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			$ref_slipno = null;
			$ref_no = time().$lib->randomText('all',3);
			try {
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
					if($responseSoap->msg_status == '0000'){
						$ref_slipno = $responseSoap->ref_slipno;
						$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
						$updateSyncNoti->execute([
							':ref_slipno' => $ref_slipno,
							':deptaccount_no' => $coop_account_no
						]);
						$flag_transaction_coop = true;
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
							':ref_no' => $ref_no,
							':from_account' => $rowDataDeposit["deptaccount_no_bank"],
							':destination' => $coop_account_no,
							':amount' => $dataComing["amt_transfer"],
							':fee_amt' => $dataComing["fee_amt"],
							':amount_receive' => $amt_transfer,
							':operate_date' => $dateOper,
							':member_no' => $payload["member_no"],
							':ref_no1' => $coop_account_no,
							':slip_no' => $ref_slipno,
							':etn_ref' => $etn_ref,
							':id_userlogin' => $payload["id_userlogin"],
							':ref_no_source' => $transaction_no,
							':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
						];
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
																	,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
																	VALUES(:ref_no,'DTB',:from_account,'1',:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'1',:member_no,
																	:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
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
						$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
								if($lib->sendNotifyHW($arrPayloadNotify,"person")){
									$func->insertHistory($arrPayloadNotify,'2');
								}
							}
						}
						$arrayResult['EXTERNAL_REF'] = $etn_ref;
						$arrayResult['TRANSACTION_NO'] = $ref_no;
						$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
						$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
						$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
						
					}else{
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,coop_slip_no,id_userlogin,ref_no_source,bank_code)
																	VALUES(:ref_no,'DTB',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:id_userlogin,:ref_no_source,:bank_code)");
						$insertTransactionLog->execute([
							':ref_no' => $ref_no,
							':from_account' => $rowDataDeposit["deptaccount_no_bank"],
							':destination' => $coop_account_no,
							':amount' => $dataComing["amt_transfer"],
							':fee_amt' => $dataComing["fee_amt"],
							':amount_receive' => $amt_transfer,
							':operate_date' => $dateOper,	
							':member_no' => $payload["member_no"],
							':ref_no1' => $coop_account_no,
							':slip_no' => $ref_slipno,
							':id_userlogin' => $payload["id_userlogin"],
							':ref_no_source' => $transaction_no,
							':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
						]);
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':sigma_key' => $dataComing["sigma_key"],
							':amt_transfer' => $amt_transfer,
							':response_code' => "WS0041",
							':response_message' => $responseSoap->msg_output,
							':is_adj' => '8',
							':ref_no' => $ref_no
						];
						$log->writeLog('deposittrans',$arrayStruc);
						$message_error = "ไม่สามารถฝากเงินได้ ให้ดู Ref_no ในตาราง gctransaction ".$ref_no." สาเหตุเพราะ ".$responseSoap->msg_output;
						$lib->sendLineNotify($message_error);
						$message_error = "มีรายการฝากมาจาก KBANK ตัดเงินเรียบร้อยแต่ไม่สามารถยิงฝากเงินเข้าบัญชีสหกรณ์ได้ เลขรหัสรายการ ".$transaction_no.
						" เลขสมาชิก ".$payload["member_no"]." เข้าบัญชี : ".$coop_account_no." ยอดทำรายการ : ".$amt_transfer." บาทเมื่อวันที่ ".$dateOper." สาเหตุที่ล้มเหลวเพราะ".$responseSoap->msg_output;
						$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
						$arrToken = $func->getFCMToken('person',$payload["member_no"]);
						$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
						foreach($arrToken["LIST_SEND"] as $dest){
							if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
								$dataMerge = array();
								$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
								$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
								$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
						$arrayResult['EXTERNAL_REF'] = $etn_ref;
						$arrayResult['TRANSACTION_NO'] = $ref_no;
						$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
						$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
						$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
						
					}
				}catch(SoapFault $e){
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,coop_slip_no,id_userlogin,ref_no_source,bank_code)
																VALUES(:ref_no,'DTB',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:id_userlogin,:ref_no_source,:bank_code)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $rowDataDeposit["deptaccount_no_bank"],
						':destination' => $coop_account_no,
						':amount' => $dataComing["amt_transfer"],
						':fee_amt' => $dataComing["fee_amt"],
						':amount_receive' => $amt_transfer,
						':operate_date' => $dateOper,	
						':member_no' => $payload["member_no"],
						':ref_no1' => $coop_account_no,
						':slip_no' => $ref_slipno,
						':id_userlogin' => $payload["id_userlogin"],
						':ref_no_source' => $transaction_no,
						':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
					]);
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':sigma_key' => $dataComing["sigma_key"],
						':amt_transfer' => $amt_transfer,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $e->getMessage(),
						':is_adj' => '8',
						':ref_no' => $ref_no
					];
					$log->writeLog('deposittrans',$arrayStruc);
					$message_error = "ไม่สามารถฝากเงินได้ ให้ดู Ref_no ในตาราง gctransaction ".$ref_no." สาเหตุเพราะ ".$e->getMessage();
					$lib->sendLineNotify($message_error);
					$message_error = "มีรายการฝากมาจาก KBANK ตัดเงินเรียบร้อยแต่ไม่สามารถยิงฝากเงินเข้าบัญชีสหกรณ์ได้ เลขรหัสรายการ ".$transaction_no.
					" เลขสมาชิก ".$payload["member_no"]." เข้าบัญชี : ".$coop_account_no." ยอดทำรายการ : ".$amt_transfer." บาทเมื่อวันที่ ".$dateOper." สาเหตุที่ล้มเหลวเพราะ".$e->getMessage();
					$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
					$arrToken = $func->getFCMToken('person',$payload["member_no"]);
						$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
						foreach($arrToken["LIST_SEND"] as $dest){
							if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
								$dataMerge = array();
								$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
								$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
								$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
						$arrayResult['EXTERNAL_REF'] = $etn_ref;
						$arrayResult['TRANSACTION_NO'] = $ref_no;
						$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
						$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
						$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
						
				}
			}catch(Throwable $e) {
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,coop_slip_no,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,'DTB',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:id_userlogin,:ref_no_source,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,	
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $transaction_no,
					':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
				]);
				$arrayResult["RESPONSE_CODE"] = 'WS9999';
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $e->getMessage(),
					':is_adj' => '8',
					':ref_no' => $ref_no
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$message_error = "ไม่สามารถฝากเงินได้ ให้ดู Ref_no ในตาราง gctransaction ".$ref_no." สาเหตุเพราะ ".$e->getMessage();
				$lib->sendLineNotify($message_error);
				$message_error = "มีรายการฝากมาจาก KBANK ตัดเงินเรียบร้อยแต่ไม่สามารถยิงฝากเงินเข้าบัญชีสหกรณ์ได้ เลขรหัสรายการ ".$transaction_no.
				" เลขสมาชิก ".$payload["member_no"]." เข้าบัญชี : ".$coop_account_no." ยอดทำรายการ : ".$amt_transfer." บาทเมื่อวันที่ ".$dateOper." สาเหตุที่ล้มเหลวเพราะ".$e->getMessage();
				$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrToken = $func->getFCMToken('person',$payload["member_no"]);
				$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
				foreach($arrToken["LIST_SEND"] as $dest){
					if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
						$dataMerge = array();
						$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
						$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
						$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
				$arrayResult['EXTERNAL_REF'] = $etn_ref;
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
				$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
				$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0038";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':sigma_key' => $dataComing["sigma_key"],
				':amt_transfer' => $amt_transfer,
				':response_code' => $arrResponse->RESPONSE_CODE,
				':response_message' => $arrResponse->RESPONSE_MESSAGE,
				':is_adj' => '0',
				':ref_no' => null
			];
			$log->writeLog('deposittrans',$arrayStruc);
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