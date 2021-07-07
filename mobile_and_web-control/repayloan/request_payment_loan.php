<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','contract_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$from_account_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$ref_no = time().$lib->randomText('all',3);
		
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			try {
				$arrayGroup = array();
				$arrayGroup["coop_id"] = $config["COOP_ID"];
				$arrayGroup["loancontract_no"] = $dataComing["contract_no"];
				$arrayGroup["member_no"] = $member_no;
				$arrayGroup["operate_date"] = $dateOperC;
				$arrayGroup["slip_date"] = $dateOperC;
				$arrayGroup["entry_id"] = $dataComing["channel"] == 'mobile_app' ? "MCOOP" : "ICOOP";
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lninitloans" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_initslippayin_mobile", array($argumentWS));
				if($resultWS->of_initslippayin_mobileResult == '1'){
					$responseInitLn = $resultWS->astr_lninitloans;
					$arrayGroupSaveLn = array();
					$arrayGroupSaveLn["coop_id"] = $coop_id;
					$arrayGroupSaveLn["loancontract_no"] = $dataComing["contract_no"];
					$arrayGroupSaveLn["member_no"] = $member_no;
					$arrayGroupSaveLn["bfshrcont_balamt"] = $responseInitLn->bfshrcont_balamt;
					$arrayGroupSaveLn["bfintarrset_amt"] = $responseInitLn->bfintarrset_amt;
					$arrayGroupSaveLn["interest_period"] = $responseInitLn->interest_period;
					$arrayGroupSaveLn["interest_payment"] = $responseInitLn->interest_period + $responseInitLn->bfintarrset_amt;
					if($arrayGroupSaveLn["interest_payment"] > $dataComing["amt_transfer"]){
						$arrayGroupSaveLn["interest_payment"] = $dataComing["amt_transfer"];
					}
					$arrayGroupSaveLn["principal_payment"] = $dataComing["amt_transfer"] - $arrayGroupSaveLn["interest_payment"];
					if($arrayGroupSaveLn["principal_payment"] < 0){
						$arrayGroupSaveLn["principal_payment"] = 0;
					}
					$arrayGroupSaveLn["slip_amt"] = $dataComing["amt_transfer"];
					$arrayGroupSaveLn["loantype_code"] = $responseInitLn->shrlontype_code;
					$arrayGroupSaveLn["period"] = $responseInitLn->period;
					$arrayGroupSaveLn["calint_from"] = $responseInitLn->calint_from;
					$arrayGroupSaveLn["bfperiod_payment"] = $responseInitLn->bfperiod_payment;
					$arrayGroupSaveLn["operate_date"] = date('c');
					$arrayGroupSaveLn["fee_amt"] = $responseInitLn->fee_amt;
					$arrayGroupSaveLn["fine_amt"] = $responseInitLn->fine_amt;
					$arrayGroupSaveLn["slip_date"] = date('c');
					$arrayGroupSaveLn["deptaccount_no"] = $from_account_no;
					$arrayGroupSaveLn["entry_id"] = $dataComing["channel"] == 'mobile_app' ? "MCOOP" : "ICOOP";
					$arrayGroupSaveDP = array();
					$arrayGroupSaveDP["coop_id"] = $coop_id;
					$arrayGroupSaveDP["member_no"] = $member_no;
					$arrayGroupSaveDP["deptaccount_no"] = $from_account_no;
					$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
						"astr_lnsave" => $arrayGroupSaveLn,
						"dept_inf_serv" => $arrayGroupSaveDP
					];
					$resultWSLN = $clientWS->__call("of_saveslip_payin_mobile", array($argumentWS));
					if($resultWSLN->of_saveslip_payin_mobileResult == '1'){
						$responseSaveLN = $resultWSLN->astr_lnsave;
						if($responseSaveLN->msg_output == '0000'){
							$fetchSeqno = $conoracle->prepare("SELECT MAX(SEQ_NO) as SEQ_NO FROM dpdeptstatement 
															WHERE   = :deptaccount_no and deptitem_amt = :slip_amt
															and TO_DATE(operate_date,'YYYY-MM-DD') = :slip_date");
							$fetchSeqno->execute([
								':deptaccount_no' => $responseSaveLN->deptaccount_no,
								':slip_amt' => $responseSaveLN->slip_amt,
								':slip_date' => $lib->convertdate($responseSaveLN->slip_date,'y-n-d')
							]);
							$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
							$id_memo  = $func->getMaxTable('id_memo' , 'gcmemodept');
							$insertRemark = $conoracle->prepare("INSERT INTO gcmemodept(id_memo ,memo_text,deptaccount_no,seq_no)
																VALUES(:id_memo ,:remark,:deptaccount_no,:seq_no)");
							$insertRemark->execute([
								':id_memo' =>  $id_memo,
								':remark' => $dataComing["remark"],
								':deptaccount_no' => $from_account_no,
								':seq_no' => $rowSeqno["SEQ_NO"]
							]);
							$arrayResult['INTEREST_PAYMENT'] = $responseSaveLN->interest_payment;
							$arrayResult['PRIN_PAYMENT'] = $responseSaveLN->principal_payment;
							$arrayResult['INTEREST_PAYMENT_FORMAT'] = number_format($responseSaveLN->interest_payment,2);
							$arrayResult['PRIN_PAYMENT_FORMAT'] = number_format($responseSaveLN->principal_payment,2);
							$arrayResult['TRANSACTION_NO'] = $ref_no;
							$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
							$insertTransactionLog = $conoracle->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
																			,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																			ref_no_1,id_userlogin,ref_no_source)
																			VALUES(:ref_no,'WTX',:from_account,'3',:destination,'2',:amount,:penalty_amt,:amount,'-1',TO_DATE(:operate_date,'yyyy-mm-dd hh24:mi:ss'),'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
							$insertTransactionLog->execute([
								':ref_no' => $ref_no,
								':from_account' => $from_account_no,
								':destination' => $dataComing["contract_no"],
								':amount' => $dataComing["amt_transfer"],
								':penalty_amt' => $dataComing["penalty_amt"] ?? 0,
								':operate_date' => $dateOper,
								':member_no' => $payload["member_no"],
								':ref_no1' => $from_account_no,
								':id_userlogin' => $payload["id_userlogin"],
								':ref_no_source' => $responseSaveLN->payinslip_no
							]);
							$arrToken = $func->getFCMToken('person',$payload["member_no"]);
							$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
							$dataMerge = array();
							$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
							$dataMerge["AMOUNT"] = number_format($dataComing["amt_transfer"],2);
							$dataMerge["CONTRACT_NO"] = $dataComing["contract_no"];
							$dataMerge["INT_PAY"] = number_format($arrayResult['INTEREST_PAYMENT'],2);
							$dataMerge["PRIN_PAY"] = number_format($arrayResult['PRIN_PAYMENT'],2);
							$dataMerge["OPERATE_DATE"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
										$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
										$updateSyncNoti->execute([
											':deptaccount_no' => $responseSaveLN->deptaccount_no,
											':seq_no' => $rowSeqno["SEQ_NO"]
										]);
										$updateSyncNoti = $conoracle->prepare("UPDATE lncontstatement SET sync_notify_flag = '1' WHERE ref_slipno = :ref_slipno");
										$updateSyncNoti->execute([':ref_slipno' => $responseSaveLN->payinslip_no]);
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
										$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
										$updateSyncNoti->execute([
											':deptaccount_no' => $responseSaveLN->deptaccount_no,
											':seq_no' => $rowSeqno["SEQ_NO"]
										]);
										$updateSyncNoti = $conoracle->prepare("UPDATE lncontstatement SET sync_notify_flag = '1' WHERE ref_slipno = :ref_slipno");
										$updateSyncNoti->execute([':ref_slipno' => $responseSaveLN->payinslip_no]);
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
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOper,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':status_flag' => '0',
								':destination' => $dataComing["contract_no"],
								':response_code' => "WS0066",
								':response_message' => $responseSaveLN->msg_output
							];
							//.$log->writeLog('repayloan',$arrayStruc);
							$arrayResult["RESPONSE_CODE"] = 'WS0066';
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}else{
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':status_flag' => '0',
							':destination' => $dataComing["contract_no"],
							':response_code' => "WS0066",
							':response_message' => json_encode($resultWSLN)
						];
						//$log->writeLog('repayloan',$arrayStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0066';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':status_flag' => '0',
						':destination' => $dataComing["contract_no"],
						':response_code' => "WS0066",
						':response_message' => json_encode($resultWS)
					];
					//$log->writeLog('repayloan',$arrayStruc);
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}catch(SoapFault $e){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':status_flag' => '0',
					':destination' => $dataComing["contract_no"],
					':response_code' => "WS0066",
					':response_message' => json_encode($e)
				];
				//$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0066",
				":error_desc" => "ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage()."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult["RESPONSE_CODE"] = 'WS0066';
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