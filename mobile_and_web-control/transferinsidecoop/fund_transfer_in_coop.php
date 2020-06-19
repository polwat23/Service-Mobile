<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','penalty_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		try {
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			$getLimitAllDay = $conoracle->prepare("SELECT total_limit FROM atmucftranslimit WHERE tran_desc = 'MOBILE_APP' and tran_status = 1");
			$getLimitAllDay->execute();
			$rowLimitAllDay = $getLimitAllDay->fetch(PDO::FETCH_ASSOC);
			$getSumAllDay = $conoracle->prepare("SELECT SUM(DEPTITEM_AMT) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') and ITEM_STATUS = '1'");
			$getSumAllDay->execute();
			$rowSumAllDay = $getSumAllDay->fetch(PDO::FETCH_ASSOC);
			$paymentAllDay = $rowSumAllDay["SUM_AMT"] + $dataComing["amt_transfer"];
			if($paymentAllDay > $rowLimitAllDay["TOTAL_LIMIT"]){
				$arrayResult["RESPONSE_CODE"] = 'WS0043';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$penalty_include = $func->getConstant("include_penalty");
			if($penalty_include == '0'){
				$recv_amt = $dataComing["amt_transfer"] - $dataComing["penalty_amt"];
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
			$arrayGroup["dest_stmitemtype_code"] = "WTX";
			$arrayGroup["entry_id"] = "mobile";
			$arrayGroup["fee_amt"] = $dataComing["penalty_amt"];
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
			$arrayGroup["stmtitemtype_code"] = "DTX";
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
				$responseSoap = $resultWS->of_withdraw_deposit_transResult;
				$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no");
				$fetchSeqno->execute([':deptslip_no' => $responseSoap->ref_slipno]);
				$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
													VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $from_account_no,
					':seq_no' => $rowSeqno["SEQ_NO"]
				]);
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																ref_no_1,id_userlogin,ref_no_source)
																VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,:amount,'-1',:operate_date,'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
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
				$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
				$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
				foreach($arrToken["LIST_SEND"] as $dest){
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
					$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}catch(SoapFault $e){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,id_userlogin)
																VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,:amount,'-1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:id_userlogin)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $from_account_no,
					':destination' => $to_account_no,
					':amount' => $dataComing["amt_transfer"],
					':operate_date' => $dateOper,
					':penalty_amt' => $dataComing["penalty_amt"],
					':member_no' => $payload["member_no"],
					':ref_no1' => $from_account_no,
					':id_userlogin' => $payload["id_userlogin"]
				]);
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
						':response_message' => $e->getMessage()
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
						':response_message' => $e->getMessage()
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0064';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0064",
				":error_desc" => "ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e)."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult["RESPONSE_CODE"] = 'WS0064';
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