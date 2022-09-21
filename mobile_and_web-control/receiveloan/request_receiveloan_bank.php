<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.citizen_id,gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,csb.itemtype_dep,csb.fee_withdraw,
												csb.link_withdraw_coopdirect,csb.bank_short_ename,gba.account_payfee
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
		$fetchDataDeposit->execute([':member_no' => $payload["member_no"]]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
		$deptaccount_no = preg_replace('/-/','',$rowDataWithdraw["deptaccount_no_bank"]);
		$itemtypeDeposit = 'DTL';
		$dataCont = $cal_loan->getContstantLoanContract($contract_no);
		$interest = $cal_loan->calculateIntArrAPI($contract_no,$dataComing["amt_transfer"]);
		if($dataComing["amt_transfer"] > ($dataCont["LOANAPPROVE_AMT"] - $dataCont["PRINCIPAL_BALANCE"])){
			$arrayResult["RESPONSE_CODE"] = 'WS0093';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$ref_no = time().$lib->randomText('all',3);
		$constFromAcc = $cal_dep->getConstantAcc($deptaccount_no);
		$fee_amt = 0;
		$time = time();
		$arrVerifyToken['exp'] = $time + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
		$arrVerifyToken['coop_account_no'] = $contract_no;
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
		$fee_amt = $rowDataWithdraw["fee_withdraw"];
		$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
		$destvcid = $cal_dep->getVcMapID($dataCont["LOANTYPE_CODE"],'LON');
		$arrSlipnoPayout = $cal_dep->generateDocNo('ONLINETXLON',$lib);
		$payoutslip_no = $arrSlipnoPayout["SLIP_NO"];
		$lastdocument_noPayout = $arrSlipnoPayout["QUERY"]["LAST_DOCUMENTNO"] + 1;
		if(isset($rowDataWithdraw["account_payfee"]) && $rowDataWithdraw["account_payfee"] != ""){
			$from_account_no = $rowDataWithdraw["account_payfee"];
			$getlastseqFeeAcc = $cal_dep->getLastSeqNo($rowDataWithdraw["account_payfee"]);
		}else{
			$fetchAccAtm = $conoracle->prepare("SELECT  DEPTACCOUNT_NO FROM dpdeptmaster WHERE member_no =:member_no AND depttype_code ='88'");
			$fetchAccAtm->execute([':member_no' => $member_no]);
			$rowAccAtm = $fetchAccAtm->fetch(PDO::FETCH_ASSOC);
			$from_account_no = $rowAccAtm["DEPTACCOUNT_NO"];	
			$getlastseqFeeAcc = $cal_dep->getLastSeqNo($rowAccAtm["DEPTACCOUNT_NO"]);			
		}
		$constFromAccFee = $cal_dep->getConstantAcc($from_account_no);
		$vccamtPenalty = $func->getConstant("accidfee_receive");
		$vccamtPenaltyPromo = $func->getConstant("accidfee_promotion");
		
		
		$updateDocuControlPayout = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXLON'");
		$updateDocuControlPayout->execute([':lastdocument_no' => $lastdocument_noPayout]);
		if($fee_amt > 0){
			$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
			$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
			$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControlFee = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
			$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
		}
		$conoracle->beginTransaction();
		$slipPayout = $cal_loan->paySlipLonOut($conoracle,$config,$payoutslip_no,$member_no,'LWD',null,$dateOper,$dataCont["LOANTYPE_CODE"],
		$contract_no,$dataComing["amt_transfer"],$payload,$deptaccount_no,'CBT',$rowDataWithdraw["bank_code"],$vccAccID,$log);
		if($slipPayout["RESULT"]){
			$receiveLon = $cal_loan->receiveLoanOD($conoracle,$config,$contract_no,$dataCont,null,$dataComing["amt_transfer"],
			$payoutslip_no,$ref_no,$deptaccount_no,0,$payload,$dataComing["app_version"],$dateOper,$log);
			if($receiveLon["RESULT"]){
				$penaltyWtd = $cal_dep->insertFeeTransaction($conoracle,$from_account_no,$vccamtPenalty,'FEM',$dataComing["amt_transfer"],$fee_amt,
				$dateOper,$config,null,$lib,$getlastseqFeeAcc["MAX_SEQ_NO"],$constFromAccFee,$payoutslip_no,$rowCountFee["C_TRANS"] + 1,$deptslip_noFee);
				if($penaltyWtd["RESULT"]){
					
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = $penaltyWtd["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':fee_amt' => $fee_amt,
						':deptaccount_no' => $contract_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtd["ACTION"]
					];
					$log->writeLog('withdrawtrans',$arrayStruc);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_withdraw_coopdirect"],$arrSendData);
				if(!$responseAPI["RESULT"]){
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0030";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':fee_amt' => $fee_amt,
						':deptaccount_no' => $contract_no,
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
					$conoracle->commit();
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	coop_slip_no,id_userlogin,ref_no_source)
																	VALUES(:ref_no,'DAP',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'-1',
																	:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $contract_no,
						':destination' => $deptaccount_no,
						':amount' => $dataComing["amt_transfer"],
						':fee_amt' => $fee_amt,
						':amount_receive' => $dataComing["amt_transfer"],
						':operate_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':slip_no' => $payoutslip_no,
						':id_userlogin' => $payload["id_userlogin"]
					]);
					$arrToken = $func->getFCMToken('person',$payload["member_no"]);
					$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($deptaccount_no,$func->getConstant('hidden_dep'));
					$dataMerge["CONTRACT_NO"] = $contract_no;
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
					$logStruc = [
						":member_no" => $payload["member_no"],
						":request_amt" => $dataComing["amt_transfer"],
						":deptaccount_no" => $deptaccount_no,
						":loancontract_no" => $contract_no,
						":status_flag" => '1',
						':id_userlogin' => $payload["id_userlogin"]
					];
					$log->writeLog('receiveloan',$logStruc);
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0037";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"] ?? 0,
						':fee_amt' => $fee_amt ?? 0,
						':deptaccount_no' => $contract_no,
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
				$conoracle->rollback();
				$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conoracle->rollback();
			$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
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