<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$ref_no = date('YmdHis').substr($from_account_no,-3);
		$dateOperC = date('c');
		$itemtype_dep = 'WAP';
		$toitemtype_dep = 'DAP';
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$amt_transfer = $dataComing["amt_transfer"] - ($dataComing["penalty_amt"] ?? 0);
		$constantDep = $cal_dep->getConstantAcc($dataComing["from_deptaccount_no"]);
		try{
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			$fetchRecpPaytype = $conoracle->prepare("SELECT default_accid FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype_dep");
			$fetchRecpPaytype->execute([':itemtype_dep' => $itemtype_dep]);
			$rowAccid = $fetchRecpPaytype->fetch(PDO::FETCH_ASSOC);
			$dateOperC = date('c');
			$arrayGroup = array();
			$arrayGroup["account_id"] = $rowAccid["DEFAULT_ACCID"];
			$arrayGroup["action_status"] = "1";
			$arrayGroup["atm_no"] = "EGAT-SC";
			$arrayGroup["atm_seqno"] = null;
			$arrayGroup["aviable_amt"] = null;
			$arrayGroup["bank_accid"] = null;
			$arrayGroup["bank_cd"] = null;
			$arrayGroup["branch_cd"] = null;
			$arrayGroup["coop_code"] = $config["COOP_KEY"];
			$arrayGroup["coop_id"] = $config["COOP_ID"];
			$arrayGroup["deptaccount_no"] = $dataComing["from_deptaccount_no"];
			$arrayGroup["depttype_code"] = $constantDep["DEPTTYPE_CODE"];
			$arrayGroup["dest_deptaccount_no"] = $dataComing["to_deptaccount_no"];
			$arrayGroup["dest_slipitemtype_code"] = $toitemtype_dep;
			$arrayGroup["dest_stmitemtype_code"] = $toitemtype_dep;
			$arrayGroup["entry_id"] = "MOBILE";
			$arrayGroup["fee_operate_cd"] = '0';
			$arrayGroup["fee_amt"] = $dataComing["penalty_amt"];
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
			$arrayGroup["slipitemtype_code"] = $itemtype_dep;
			$arrayGroup["stmtitemtype_code"] = $itemtype_dep;
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_PASS"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
				$responseSoap = $resultWS->of_withdraw_deposit_transResult;
				if($responseSoap->msg_status == '0000'){
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	ref_no_1,id_userlogin,ref_no_source)
																	VALUES(:ref_no,'WTB',:from_account,:destination,'1',:amount,:penalty_amt,:amount_receive,'-1',:operate_date,'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $from_account_no,
						':destination' => $to_account_no,
						':amount' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':amount_receive' => $amt_transfer,
						':operate_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':ref_no1' => $from_account_no,
						':id_userlogin' => $payload["id_userlogin"],
						':ref_no_source' => $responseSoap->ref_slipno
					]);
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,ref_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':remark' => $dataComing["remark"] ?? null,
						':deptaccount_no' => $from_account_no,
						':seq_no' => $constantDep["LASTSTMSEQ_NO"] + 1
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
							$arrPayloadNotify["SEND_BY"] = 'system';
							$arrPayloadNotify["TYPE_NOTIFY"] = '2';
							if($func->insertHistory($arrPayloadNotify,'2')){
								$lib->sendNotify($arrPayloadNotify,"person");
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
							$arrPayloadNotify["SEND_BY"] = 'system';
							$arrPayloadNotify["TYPE_NOTIFY"] = '2';
							if($func->insertHistory($arrPayloadNotify,'2')){
								$lib->sendNotifyHW($arrPayloadNotify,"person");
							}
						}
					}
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS8001';
					if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':type_request' => '2',
							':transfer_flag' => '2',
							':destination' => $to_account_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => json_encode($responseSoap,JSON_UNESCAPED_UNICODE)
						];
					}else{
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':deptaccount_no' => $from_account_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':type_request' => '2',
							':transfer_flag' => '1',
							':destination' => $to_account_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => json_encode($responseSoap,JSON_UNESCAPED_UNICODE)
						];
					}
					$log->writeLog('transferinside',$arrayStruc);
					if(isset($configError["SAVING_EGAT_ERR"][0][$responseSoap->msg_status][0][$lang_locale])){
						$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$responseSoap->msg_status][0][$lang_locale];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}catch(SoapFault $e){
				$arrayResult["RESPONSE_CODE"] = 'WS8001';
				if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => date('Y-m-d H:i:s'),
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $to_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $e->getMessage()
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => date('Y-m-d H:i:s'),
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $to_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $e->getMessage()
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => "Cannot connect server Deposit API ".$config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl";
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
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