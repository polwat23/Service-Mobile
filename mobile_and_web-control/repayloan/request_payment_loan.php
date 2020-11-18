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
				$arrayGroup["entry_id"] = "mobile_app";
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lninitloans" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_initslippayin_mobile", array($argumentWS));
				$responseInitLn = $resultWS->of_initslippayin_mobileResult;
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
				$arrayGroupSaveLn["slip_date"] = date('c');
				$arrayGroupSaveLn["entry_id"] = "mobile_app";
				$arrayGroupSaveDP = array();
				$arrayGroupSaveDP["coop_id"] = $coop_id;
				$arrayGroupSaveDP["member_no"] = $member_no;
				$arrayGroupSaveDP["deptaccount_no"] = $from_account_no;
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lnsave" => $arrayGroupSaveLn,
					"dept_inf_serv" => $arrayGroupSaveDP
				];
				$resultWS = $clientWS->__call("of_saveslip_payin_mobile", array($argumentWS));
				$responseSaveLN = $resultWS->of_saveslip_payin_mobileResult;
				$arrayResult['WWWW'] = $responseSaveLN;
				if($responseSaveLN->msg_output == '0000'){
					$fetchSeqno = $conoracle->prepare("SELECT MAX(SEQ_NO) as SEQ_NO FROM dpdeptstatement 
													WHERE deptaccount_no = :deptaccount_no and deptitem_amt = :slip_amt
													and to_char(operate_date,'YYYY-MM-DD') = :slip_date");
					$fetchSeqno->execute([
						':deptaccount_no' => $responseSaveLN->deptaccount_no,
						':slip_amt' => $responseSaveLN->slip_amt,
						':slip_date' => $lib->convertdate($responseSaveLN->slip_date,'y-n-d')
					]);
					$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
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
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	ref_no_1,id_userlogin,ref_no_source)
																	VALUES(:ref_no,'WTX',:from_account,'3',:destination,'2',:amount,:penalty_amt,:amount,'-1',:operate_date,'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
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
						':ref_no_source' => $responseSaveLN->deptslip_no
					]);
					$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
					$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMOUNT"] = number_format($dataComing["amt_transfer"],2);
					$dataMerge["CONTRACT_NO"] = $dataComing["contract_no"];
					$dataMerge["INT_PAY"] = $arrayResult['INTEREST_PAYMENT'];
					$dataMerge["PRIN_PAY"] = $arrayResult['PRIN_PAYMENT'];
					$dataMerge["OPERATE_DATE"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
					$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
					foreach($arrToken["LIST_SEND"] as $dest){
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
					}
					/*$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno");
					$updateSyncNoti->execute([':ref_slipno' => $responseSaveLN->deptslip_no]);*/
					/*$updateSyncNoti = $conoracle->prepare("UPDATE lncontstatement SET sync_notify_flag = '1' WHERE ref_slipno = :ref_slipno");
					$updateSyncNoti->execute([':ref_slipno' => $responseSaveLN->payinslip_no]);*/
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
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
					$log->writeLog('repayloan',$arrayStruc);
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
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
					':response_message' => ($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>