<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_dep,csb.itemtype_dep,csb.link_deposit_coopdirect,
												csb.bank_short_ename,gba.account_payfee,csb.fee_deposit
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$ref_no = time().$lib->randomText('all',3);
		$penalty_include = $func->getConstant("include_penalty");
		if($penalty_include == '0'){
			$amt_transfer = $dataComing["amt_transfer"] - $dataComing["fee_amt"];
		}else{
			$amt_transfer = $dataComing["amt_transfer"];
		}
		$vccAccID = null;
		if($rowDataDeposit["bank_code"] == '025'){
			$vccAccID = $func->getConstant('map_account_id_bay');
		}else if($rowDataDeposit["bank_code"] == '006'){
			$vccAccID = $func->getConstant('map_account_id_ktb');
		}
		$arrSlipDPnoDest = $cal_dep->generateDocNo('ONLINETX',$lib);
		$deptslip_noDest = $arrSlipDPnoDest["SLIP_NO"];
		$getBalanceAccFee = $conmssql->prepare("SELECT PRNCBAL FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$getBalanceAccFee->execute([':deptaccount_no' => $rowDataDeposit["account_payfee"]]);
		$rowBalFee = $getBalanceAccFee->fetch(PDO::FETCH_ASSOC);
		$getTransactionForFee = $conmysql->prepare("SELECT COUNT(ref_no) as C_TRANS FROM gctransaction WHERE member_no = :member_no and trans_flag = '1' and
													transfer_mode = '9' and result_transaction = '1' and DATE_FORMAT(operate_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')");
		$getTransactionForFee->execute([
			':member_no' => $payload["member_no"]
		]);
		$rowCountFee = $getTransactionForFee->fetch(PDO::FETCH_ASSOC);
		$lastdocument_noDest = $arrSlipDPnoDest["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$updateDocuControl = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETX'");
		$updateDocuControl->execute([':lastdocument_no' => $lastdocument_noDest]);
		$dataAccFee = $cal_dep->getConstantAcc($rowDataDeposit["account_payfee"]);
		$conmssql->beginTransaction();
		$conmysql->beginTransaction();
		$getlastseq_noDest = $cal_dep->getLastSeqNo($coop_account_no);
		$getlastseqFeeAcc = $cal_dep->getLastSeqNo($rowDataDeposit["account_payfee"]);
		if($rowCountFee["C_TRANS"] + 1 > 1){
			$depositMoney = $cal_dep->DepositMoneyInside($conmssql,$coop_account_no,$vccAccID,$rowDataDeposit["itemtype_dep"],
			$amt_transfer,$rowDataDeposit["fee_deposit"],$dateOper,$config,$log,$rowDataDeposit["deptaccount_no_bank"],$payload,$deptslip_noDest,$lib,
			$getlastseq_noDest["MAX_SEQ_NO"],$dataComing["menu_component"],$ref_no,true,null,$rowDataDeposit["bank_code"],$dataComing["sigma_key"],$rowCountFee["C_TRANS"] + 1);
		}else{
			$depositMoney = $cal_dep->DepositMoneyInside($conmssql,$coop_account_no,$vccAccID,$rowDataDeposit["itemtype_dep"],
			$amt_transfer,0,$dateOper,$config,$log,$rowDataDeposit["deptaccount_no_bank"],$payload,$deptslip_noDest,$lib,
			$getlastseq_noDest["MAX_SEQ_NO"],$dataComing["menu_component"],$ref_no,true,null,$rowDataDeposit["bank_code"],$dataComing["sigma_key"],$rowCountFee["C_TRANS"] + 1);
		}
		if($depositMoney["RESULT"]){
			if($coop_account_no == $rowDataDeposit["account_payfee"]){
				$dataAccFee = $depositMoney["DATA_CONT"];
			}else{
				$depositMoney["MAX_SEQNO"] = $getlastseqFeeAcc["MAX_SEQ_NO"];
			}
			if($rowCountFee["C_TRANS"] + 1 > 1){
				if($rowDataDeposit["fee_deposit"] > 0){
					if($rowBalFee["PRNCBAL"] - $rowDataDeposit["fee_deposit"] < $dataAccFee["MINPRNCBAL"]){
						$conmssql->rollback();
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS0100";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
					$vccamtPenalty = $func->getConstant("accidfee_receive");
					$from_account_no = $rowDataDeposit["account_payfee"];
					$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
					$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
					$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
					$updateDocuControlFee = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
					$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
					$penaltyWtd = $cal_dep->insertFeeTransaction($conmssql,$from_account_no,$vccamtPenalty,'FDM',
					$dataComing["amt_transfer"],$rowDataDeposit["fee_deposit"],$dateOper,$config,$depositMoney["DEPTSLIP_NO"],$lib,$depositMoney["MAX_SEQNO"],$dataAccFee,false,null,$rowCountFee["C_TRANS"] + 1,$deptslip_noFee);
					if($penaltyWtd["RESULT"]){
						
					}else{
						$conmssql->rollback();
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = $penaltyWtd["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':sigma_key' => $dataComing["sigma_key"],
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtd["ACTION"]
						];
						$log->writeLog('deposittrans',$arrayStruc);
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}
			}else{
				if($rowDataDeposit["fee_deposit"] > 0){
					$vccamtPenaltyDepPromo = $func->getConstant("accidfee_promotion");
					$from_account_no = $rowDataDeposit["account_payfee"];
					$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
					$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
					$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
					$updateDocuControlFee = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
					$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
					$penaltyWtdPromo = $cal_dep->insertFeePromotion($conmssql,$from_account_no,$vccamtPenaltyDepPromo,'FDM',
					$dataComing["amt_transfer"],$rowDataDeposit["fee_deposit"],$dateOper,$config,$depositMoney["DEPTSLIP_NO"],$lib,$depositMoney["MAX_SEQNO"],$dataAccFee,$rowCountFee["C_TRANS"] + 1,$deptslip_noFee);
					if($penaltyWtdPromo["RESULT"]){
						
					}else{
						$conmssql->rollback();
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = $penaltyWtdPromo["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':sigma_key' => $dataComing["sigma_key"],
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtdPromo["ACTION"]
						];
						$log->writeLog('deposittrans',$arrayStruc);
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}
			}
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
			}
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			// Deposit Inside --------------------------------------
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataDeposit["link_deposit_coopdirect"],$arrSendData);
			if(!$responseAPI["RESULT"]){
				$conmssql->rollback();
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$arrayResult['RESPONSE_CODE'] = "WS0027";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$transaction_no = $arrResponse->TRANSACTION_NO;
				$etn_ref = $arrResponse->EXTERNAL_REF;
				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
													VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $coop_account_no,
					':seq_no' => $getlastseq_noDest["MAX_SEQ_NO"] + 1
				]);
				$arrExecute = [
					':ref_no' => $ref_no,
					':slip_type' => 'DIM',
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $dataComing["amt_transfer"],
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':slip_no' => $deptslip_noDest,
					':etn_refno' => $etn_ref,
					':ref_source' => $transaction_no,
					':id_userlogin' => $payload["id_userlogin"],
					':bank_code' => $rowDataDeposit["bank_code"]
				];
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,:slip_type,:from_account,:destination,'9',:amount,:fee_amt,
															:amount_receive,'1',:operate_date,'1',:member_no,:slip_no,:etn_refno,:id_userlogin,:ref_source,:bank_code)");
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
				$conmssql->commit();
				$conmysql->commit();
				$arrayResult['EXTERNAL_REF'] = $etn_ref;
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$conmssql->rollback();
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0038";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $dataComing["amt_transfer"],
					':response_code' => $arrResponse->RESPONSE_CODE,
					':response_message' => $arrResponse->RESPONSE_MESSAGE
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
			$conmssql->rollback();
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = $depositMoney["RESPONSE_CODE"];
			if($depositMoney["RESPONSE_CODE"] == "WS0056"){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($depositMoney["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
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