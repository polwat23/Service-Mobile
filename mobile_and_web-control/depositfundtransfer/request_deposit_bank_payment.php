<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_dep,csb.link_deposit_coopdirect,csb.bank_short_ename
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$bank_account_no = preg_replace('/-/','',$dataComing["dept_account_enc"]);
		$time = time();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$amt_transfer = $dataComing["amt_transfer"];
		$ref_no = time().$lib->randomText('all',3);
		$arrSendData = array();
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['ref_trans'] = $ref_no;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		if($rowDataDeposit["bank_code"] == '025'){
			$arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
			$arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
		}else if($rowDataDeposit["bank_code"] == '014'){
			$arrVerifyToken['bank_account_no'] = $rowDataDeposit["deptaccount_no_bank"];
		}
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$ref_slipno = null;
		// Deposit Inside --------------------------------------
		$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataDeposit["link_deposit_coopdirect"],$arrSendData);
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
				':is_adj' => '0'
			];
			$log->writeLog('deposittrans',$arrayStruc);
			$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
			$lib->sendLineNotify($message_error);
			$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponse = json_decode($responseAPI);
		if($arrResponse->RESULT){
			$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET confirm_status = 9
													WHERE deptaccount_no = :deptaccount_no");
			$updateWithdrawData->execute([
				':deptaccount_no' => $coop_account_no
			]);
			$transaction_no = $arrResponse->TRANSACTION_NO;
			$etn_ref = $arrResponse->EXTERNAL_REF;
			$constantDep = $cal_dep->getConstantAcc($coop_account_no);
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			$fetchRecpPaytype = $conoracle->prepare("SELECT default_accid FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype_dep");
			$fetchRecpPaytype->execute([':itemtype_dep' => $rowDataDeposit["itemtype_dep"]]);
			$rowAccid = $fetchRecpPaytype->fetch(PDO::FETCH_ASSOC);
			$dateOperC = date('c');
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
			$arrayGroup["deptgroup_code"] = $constantDep["DEPTGROUP_CODE"];
			$arrayGroup["entry_id"] = "MOBILE";
			$arrayGroup["fee_amt"] = 0;
			$arrayGroup["fee_operate_cd"] = '0';
			$arrayGroup["feeinclude_status"] = '1';
			$arrayGroup["item_amt"] = $amt_transfer;
			$arrayGroup["laststmseq_no"] = $constantDep["LASTSTMSEQ_NO"];
			$arrayGroup["membcat_code"] = $constantDep["MEMBCAT_CODE"];
			$arrayGroup["member_no"] = $member_no;
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = $dateOperC;
			$arrayGroup["oprate_cd"] = "003";
			$arrayGroup["post_status"] = "1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_app"] = "MOBILE";
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = $rowDataDeposit["itemtype_dep"];
			$arrayGroup["stmtitemtype_code"] = $rowDataDeposit["itemtype_dep"];
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = 0;
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_PASS"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_insert_serv_online", array($argumentWS));
				$responseSoap = $resultWS->of_dept_insert_serv_onlineResult;
				if($responseSoap->msg_status != '0000'){
					$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET confirm_status = 8,last_error = 'repost Deposit ".$ref_no."'
															WHERE deptaccount_no = :deptaccount_no");
					$updateWithdrawData->execute([
						':deptaccount_no' => $coop_account_no
					]);
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,
																coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
																VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',
																:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:etn_refno,:id_userlogin,:ref_no_source,:bank_code)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':itemtype' => $rowDataDeposit["itemtype_dep"],
						':from_account' => $bank_account_no,
						':destination' => $coop_account_no,
						':amount' => $dataComing["amt_transfer"],
						':fee_amt' => $dataComing["fee_amt"],
						':amount_receive' => $amt_transfer,
						':operate_date' => $dateOper,	
						':member_no' => $payload["member_no"],
						':ref_no1' => $coop_account_no,
						':slip_no' => $ref_slipno,
						':etn_refno' => $etn_ref,
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
						':response_message' => json_encode($responseSoap,JSON_UNESCAPED_UNICODE )
					];
					$log->writeLog('deposittrans',$arrayStruc);
					$message_error = "ไม่สามารถฝากเงินได้ ให้ดู Ref_no ในตาราง gctransaction ".$ref_no." สาเหตุเพราะ ติดต่อ Service เงินฝากไม่ได้";
					$lib->sendLineNotify($message_error);
					$message_error = "มีรายการฝากมาจาก ".$rowDataDeposit["bank_short_ename"]." ตัดเงินเรียบร้อยแต่ไม่สามารถยิงฝากเงินเข้าบัญชีสหกรณ์ได้ เลขรหัสรายการ ".$etn_ref.
					" เลขสมาชิก ".$payload["member_no"]." เข้าบัญชี : ".$coop_account_no." ยอดทำรายการ : ".$amt_transfer." บาทเมื่อวันที่ ".$dateOper." สาเหตุที่ล้มเหลวเพราะ Server cannot connect";
					$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
					$func->MaintenanceMenu($dataComing["menu_component"]);
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
					$arrayResult['EXTERNAL_REF'] = $etn_ref;
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET withdrawable_amt = withdrawable_amt + ".$amt_transfer."
															WHERE deptaccount_no = :deptaccount_no");
					$updateWithdrawData->execute([
						':deptaccount_no' => $coop_account_no
					]);
					$ref_slipno = $responseSoap->ref_slipno;
					// -----------------------------------------------
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,ref_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $coop_account_no,
						':seq_no' => $ref_slipno
					]);
					$arrExecute = [
						':ref_no' => $ref_no,
						':itemtype' => $rowDataDeposit["itemtype_dep"],
						':from_account' => $bank_account_no,
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
																VALUES(:ref_no,:itemtype,:from_account,'1',:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'1',:member_no,
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
					$arrayResult['EXTERNAL_REF'] = $etn_ref;
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}
			}catch(SoapFault $e){
				$updateWithdrawData = $conoracle->prepare("UPDATE dpdeptmaster SET confirm_status = -9
														WHERE deptaccount_no = :deptaccount_no");
				$updateWithdrawData->execute([
					':deptaccount_no' => $coop_account_no
				]);
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,
															coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',
															:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:etn_refno,:id_userlogin,:ref_no_source,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':itemtype' => $rowDataDeposit["itemtype_dep"],
					':from_account' => $bank_account_no,
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,	
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':etn_refno' => $etn_ref,
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
					':response_message' => json_encode($e,JSON_UNESCAPED_UNICODE )
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$message_error = "ไม่สามารถฝากเงินได้ ให้ดู Ref_no ในตาราง gctransaction ".$ref_no." สาเหตุเพราะ ติดต่อ Service เงินฝากไม่ได้";
				$lib->sendLineNotify($message_error);
				$message_error = "มีรายการฝากมาจาก ".$rowDataDeposit["bank_short_ename"]." ตัดเงินเรียบร้อยแต่ไม่สามารถยิงฝากเงินเข้าบัญชีสหกรณ์ได้ เลขรหัสรายการ ".$etn_ref.
				" เลขสมาชิก ".$payload["member_no"]." เข้าบัญชี : ".$coop_account_no." ยอดทำรายการ : ".$amt_transfer." บาทเมื่อวันที่ ".$dateOper." สาเหตุที่ล้มเหลวเพราะ Server cannot connect";
				$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
				$func->MaintenanceMenu($dataComing["menu_component"]);
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
				$arrayResult['EXTERNAL_REF'] = $etn_ref;
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
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
				':response_message' => json_encode($arrResponse->RESPONSE_MESSAGE)
			];
			$log->writeLog('deposittrans',$arrayStruc);
			if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>