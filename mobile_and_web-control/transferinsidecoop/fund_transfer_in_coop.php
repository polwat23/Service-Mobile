<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		try {
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			
			$penalty_include = $func->getConstant("include_penalty");
			if($penalty_include == '0'){
				$recv_amt = $dataComing["amt_transfer"] - $dataComing["fee_amt"];
			}else{
				$recv_amt = $dataComing["amt_transfer"];
			}
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
			$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
			$dateOperC = date('c');
			$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
			$ref_no = time().$lib->randomText('all',3);
			$arrayGroup = array();
			$arrayGroup["account_id"] = $func->getConstant("operative_account");
			$arrayGroup["action_status"] = "1";
			$arrayGroup["atm_no"] = "mobile";
			$arrayGroup["atm_seqno"] = null;
			$arrayGroup["aviable_amt"] = null;
			$arrayGroup["bank_accid"] = null;
			$arrayGroup["bank_cd"] = null;
			$arrayGroup["branch_cd"] = null;
			$arrayGroup["coop_code"] = $config["COOP_KEY"];
			$arrayGroup["coop_id"] = $config["COOP_ID"];
			$arrayGroup["deptaccount_no"] = $from_account_no;
			$arrayGroup["depttype_code"] = null;
			$arrayGroup["dest_deptaccount_no"] = $to_account_no;
			$arrayGroup["dest_slipitemtype_code"] = "DTX";
			$arrayGroup["dest_stmitemtype_code"] = "DTX";
			$arrayGroup["entry_id"] = $dataComing["channel"] == 'mobile_app' ? "MCOOP" : "ICOOP";
			$arrayGroup["fee_amt"] = $dataComing["fee_amt"];
			$arrayGroup["feeinclude_status"] = $penalty_include;
			$arrayGroup["item_amt"] = $dataComing["amt_transfer"];
			$arrayGroup["member_no"] = $member_no;
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = $dateOperC;
			$arrayGroup["oprate_cd"] = "002";
			$arrayGroup["post_status"] = "1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_app"] = "mobile";
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = "WTX";
			$arrayGroup["stmtitemtype_code"] = "WTX";
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
				$responseSoap = $resultWS->of_withdraw_deposit_transResult;
				if($responseSoap->msg_status == '0000'){
					$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no");
					$fetchSeqno->execute([':deptslip_no' => $responseSoap->ref_slipno]);
					$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
					$id_memo  = $func->getMaxTable('id_memo' , 'gcmemodept');
					$insertRemark = $conoracle->prepare("INSERT INTO gcmemodept(id_memo ,memo_text,deptaccount_no,seq_no)
														VALUES(:id_memo,:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':id_memo' => $id_memo,
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $from_account_no,
						':seq_no' => $rowSeqno["SEQ_NO"]
					]);
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$insertTransactionLog = $conoracle->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	ref_no_1,id_userlogin,ref_no_source)
																	VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,:amount,'-1',TO_DATE(:operate_date,'yyyy/mm/dd hh24:mi:ss'),'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $from_account_no,
						':destination' => $to_account_no,
						':amount' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':operate_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':ref_no1' => $from_account_no,
						':id_userlogin' => $payload["id_userlogin"],
						':ref_no_source' => $responseSoap->ref_slipno
					]);
					$arrToken = $func->getFCMToken('person',$payload["member_no"]);
					$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
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
							$arrPayloadNotify["TYPE_NOTIFY"] = "2";
							if($lib->sendNotify($arrPayloadNotify,"person")){
								$func->insertHistory($arrPayloadNotify,'2');
								$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno");
								$updateSyncNoti->execute([':ref_slipno' => $responseSoap->ref_slipno]);
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
								$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno");
								$updateSyncNoti->execute([':ref_slipno' => $responseSoap->ref_slipno]);
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
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':type_request' => '2',
							':transfer_flag' => '2',
							':destination' => $to_account_no,
							':response_code' => "WS0064",
							':response_message' => $responseSoap->msg_output
						];
					}else{
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':type_request' => '2',
							':transfer_flag' => '1',
							':destination' => $to_account_no,
							':response_code' => "WS0064",
							':response_message' => $responseSoap->msg_output
						];
					}
					$log->writeLog('transferinside',$arrayStruc);
					$arrayResult["RESPONSE_CODE"] = 'WS0064';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}catch(SoapFault $e){
				if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["fee_amt"],
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $to_account_no,
						':response_code' => "WS0064",
						':response_message' => $e->getMessage()
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["fee_amt"],
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $to_account_no,
						':response_code' => "WS0064",
						':response_message' => $e->getMessage()
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0064';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0064",
				":error_desc" => "ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".$e->getMessage()."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult["RESPONSE_CODE"] = 'WS0064';
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