<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.citizen_id,gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.itemtype_dep,csb.fee_withdraw,
												csb.link_withdraw_coopdirect,csb.bank_short_ename,gba.account_payfee
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
		$getTransactionForFee = $conmysql->prepare("SELECT COUNT(ref_no) as C_TRANS FROM gctransaction WHERE member_no = :member_no and trans_flag = '-1' and
													transfer_mode = '9' and result_transaction = '1' and MONTH(operate_date) = MONTH(NOW())");
		$getTransactionForFee->execute([
			':member_no' => $payload["member_no"]
		]);
		$rowCountFee = $getTransactionForFee->fetch(PDO::FETCH_ASSOC);
		if($rowCountFee["C_TRANS"] + 1 > 2){
			$dataComing["fee_amt"] = $dataComing["fee_amt"];
		}else{
			$dataComing["fee_amt"] = 0;
		}
		if($rowDataWithdraw["bank_code"] == '025'){
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
		$vccAccID = null;
		if($rowDataWithdraw["bank_code"] == '004'){
			$arrVerifyToken["tran_id"] = $dataComing["tran_id"];
			$arrVerifyToken["kbank_ref_no"] = $dataComing["kbank_ref_no"];
			$arrVerifyToken['citizen_id_enc'] = $dataComing["citizen_id_enc"];
			$arrVerifyToken['dept_account_enc'] = $dataComing["dept_account_enc"];
			$refbank_no = $dataComing["kbank_ref_no"];
		}else if($rowDataWithdraw["bank_code"] == '006'){
			$vccAccID = $func->getConstant('map_account_id_ktb');
			$arrVerifyToken['tran_date'] = $dateOper;
			$arrVerifyToken['bank_account'] = $rowDataWithdraw["deptaccount_no_bank"];
			$arrVerifyToken['citizen_id'] = $rowDataWithdraw["citizen_id"];
		}else if($rowDataWithdraw["bank_code"] == '025'){
			$vccAccID = $func->getConstant('map_account_id_bay');
			$arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
			$arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
			$refbank_no = $dataComing["SOURCE_REFNO"];
			$etnrefbank_no = $dataComing["ETN_REFNO"];
		}
		
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$arrSlipDPno = $cal_dep->generateDocNo('DPSLIPNO',$lib);
		$deptslip_no = $arrSlipDPno["SLIP_NO"];
		$lastdocument_no = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 2;
		$getlastseq_no = $cal_dep->getLastSeqNo($coop_account_no);
		$updateDocuControl = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
		$updateDocuControl->execute([':lastdocument_no' => $lastdocument_no]);
		$constFromAcc = $cal_dep->getConstantAcc($coop_account_no);
		$from_account_no = $rowDataWithdraw["account_payfee"];
		$constFromAccFee = $cal_dep->getConstantAcc($from_account_no);
		$vccamtPenalty = $func->getConstant("accidfee_receive");
		$vccamtPenaltyPromo = $func->getConstant("accidfee_promotion");
		$getlastseqFeeAcc = $cal_dep->getLastSeqNo($rowDataDeposit["account_payfee"]);
		$conmssql->beginTransaction();
		$wtdResult = $cal_dep->WithdrawMoneyInside($conmssql,$coop_account_no,$vccAccID,$rowDataWithdraw["itemtype_wtd"],$dataComing["amt_transfer"],
		$fee_amt,$dateOper,$config,$log,$payload,$deptslip_no,$lib,$getlastseq_no["MAX_SEQ_NO"],$constFromAcc,$rowCountFee["C_TRANS"] + 1);
		if($wtdResult["RESULT"]){
			$ref_slipno = $wtdResult["DEPTSLIP_NO"];
			if($coop_account_no == $from_account_no){
				$constFromAccFee = $wtdResult["DATA_CONT"];
			}else{
				$wtdResult["MAX_SEQNO"] = $getlastseqFeeAcc["MAX_SEQ_NO"];
			}
			if($fee_amt > 0){
				$penaltyWtd = $cal_dep->insertFeeTransaction($conmssql,$from_account_no,$vccamtPenalty,'FWM',
				$dataComing["amt_transfer"],$fee_amt,$dateOper,$config,$wtdResult["DEPTSLIP_NO"],$lib,$wtdResult["MAX_SEQNO"],$constFromAccFee,false,null,$rowCountFee["C_TRANS"] + 1);
				if($penaltyWtd["RESULT"]){
					
				}else{
					$conmssql->rollback();
					$arrayResult['RESPONSE_CODE'] = $penaltyWtd["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':fee_amt' => $fee_amt,
						':deptaccount_no' => $coop_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtd["ACTION"]
					];
					$log->writeLog('withdrawtrans',$arrayStruc);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				if($rowCountFee["C_TRANS"] + 1 > 2){
				}else{
					$penaltyWtdPromo = $cal_dep->insertFeePromotion($conmssql,$from_account_no,$vccamtPenaltyPromo,'FWM',
					$dataComing["amt_transfer"],$rowDataWithdraw["fee_withdraw"],$dateOper,$config,$wtdResult["DEPTSLIP_NO"],$lib,$wtdResult["MAX_SEQNO"],$constFromAccFee,$rowCountFee["C_TRANS"] + 1);
					if($penaltyWtdPromo["RESULT"]){
						
					}else{
						$conmssql->rollback();
						$arrayResult['RESPONSE_CODE'] = $penaltyWtdPromo["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':amt_transfer' => $dataComing["amt_transfer"],
							':penalty_amt' => $dataComing["penalty_amt"],
							':fee_amt' => $fee_amt,
							':deptaccount_no' => $coop_account_no,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtdPromo["ACTION"]
						];
						$log->writeLog('withdrawtrans',$arrayStruc);
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}
			}
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_withdraw_coopdirect"],$arrSendData);
			if(!$responseAPI["RESULT"]){
				$conmssql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0030";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':fee_amt' => $fee_amt,
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
				$conmssql->commit();
				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
													VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $coop_account_no,
					':seq_no' => $getlastseq_no["MAX_SEQ_NO"] + 1
				]);
				$arrExecute = [
					':ref_no' => $ref_no,
					':itemtype' => $rowDataWithdraw["itemtype_wtd"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataWithdraw["deptaccount_no_bank"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $dataComing["amt_transfer"],
					':oper_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':etn_ref' => $etnrefbank_no,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $refbank_no,
					':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
				];
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'1',:member_no,:ref_no1,
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
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$conmssql->rollback();
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
			$conmssql->rollback();
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
				':response_message' => $wtdResult["ACTION"]
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