<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','receiveloanod')){
		if($dataComing["is_status"] == '1'){
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
			$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
			if(isset($dataComing["is_bank"]) && $dataComing["is_bank"] == '1'){
				$dataCont = $cal_loan->getContstantLoanContract($contract_no);
				$interest = $cal_loan->calculateIntArrAPI($contract_no,$dataComing["amt_transfer"]);
				if($dataComing["amt_transfer"] > $dataCont["WITHDRAWABLE_AMT"]){
					$arrayResult["RESPONSE_CODE"] = 'WS0093';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
				$dateOperC = date('c');
				$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
				$ref_no = time().$lib->randomText('all',3);
				$destvcid = $cal_dep->getVcMapID($dataCont["LOANTYPE_CODE"],'LON');
				$arrSlipnoPayout = $cal_dep->generateDocNo('SLSLIPPAYOUT',$lib);
				$payoutslip_no = $arrSlipnoPayout["SLIP_NO"];
				$lastdocument_noPayout = $arrSlipnoPayout["QUERY"]["LAST_DOCUMENTNO"] + 1;
				$updateDocuControlPayout = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLSLIPPAYOUT'");
				$updateDocuControlPayout->execute([':lastdocument_no' => $lastdocument_noPayout]);
				$getlastseq_noDest = $cal_dep->getLastSeqNo($deptaccount_no);
				$conoracle->beginTransaction();
				$slipPayout = $cal_loan->paySlipLonOut($conoracle,$config,$payoutslip_no,$member_no,'LWD',$lastdocument_noPayout,$dateOper,$dataCont["LOANTYPE_CODE"],
				$contract_no,$dataComing["amt_transfer"],$dataComing["payload"],$deptaccount_no,'TRN',null,null,$log);
				if($slipPayout["RESULT"]){
					$receiveLon = $cal_loan->receiveLoanOD($conoracle,$config,$contract_no,$dataCont,null,$dataComing["amt_transfer"],
					$payoutslip_no,$ref_no,$deptaccount_no,0,$dataComing["payload"],'0.0.1',$dateOper,$log);
					if($receiveLon["RESULT"]){
						$conoracle->commit();
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																		,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																		coop_slip_no,id_userlogin,ref_no_source)
																		VALUES(:ref_no,'DAP',:from_account,:destination,'4',:amount,:penalty_amt,:amount_receive,'-1',
																		:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
						if($insertTransactionLog->execute([
							':ref_no' => $ref_no,
							':from_account' => $contract_no,
							':destination' => $deptaccount_no,
							':amount' => $dataComing["amt_transfer"],
							':penalty_amt' => 0,
							':amount_receive' => $dataComing["amt_transfer"],
							':operate_date' => $dateOper,
							':member_no' => $dataComing["payload"]["member_no"],
							':slip_no' => $payoutslip_no,
							':id_userlogin' => $dataComing["payload"]["id_userlogin"]
						])){
							$insertIntoInfo = $conmysql->prepare("UPDATE gcreceiveloanod SET receive_stauts =:is_status WHERE member_no = :member_no AND id_receiveloan = :id_receiveloan");
							if($insertIntoInfo->execute([
								':is_status' => $dataComing["is_status"],
								':member_no' => $dataComing["member_no"], 
								':id_receiveloan' => $dataComing["id_receiveloan"]
							])){
								$arrayResult['RESULT'] = TRUE;
							}else{
								$arrayResult['RESPONSE_MESSAGE'] = "ไม่สำเร็จ";
								$arrayResult['RESULT'] = FALSE;
								require_once('../../../../include/exit_footer.php');
							}
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "ไม่สำเร็จ";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
						$arrToken = $func->getFCMToken('person',$dataComing["payload"]["member_no"]);
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
									$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
									$updateSyncNoti->execute([
										':ref_slipno' => $deptslip_noDest,
										':deptaccount_no' => $deptaccount_no
									]);
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
									$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
									$updateSyncNoti->execute([
										':ref_slipno' => $deptslip_noDest,
										':deptaccount_no' => $deptaccount_no
									]);
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
						require_once('../../../../include/exit_footer.php');
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$itemtypeDeposit = 'DTL';
				$dataCont = $cal_loan->getContstantLoanContract($contract_no);
				$interest = $cal_loan->calculateIntArrAPI($contract_no,$dataComing["amt_transfer"]);
				if($dataComing["amt_transfer"] > $dataCont["WITHDRAWABLE_AMT"]){
					$arrayResult["RESPONSE_CODE"] = 'WS0093';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
				$dateOperC = date('c');
				$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
				$ref_no = time().$lib->randomText('all',3);
				$constFromAcc = $cal_dep->getConstantAcc($deptaccount_no);
				$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
				$destvcid = $cal_dep->getVcMapID($dataCont["LOANTYPE_CODE"],'LON');
				$arrSlipnoPayout = $cal_dep->generateDocNo('SLSLIPPAYOUT',$lib);
				$payoutslip_no = $arrSlipnoPayout["SLIP_NO"];
				$arrSlipDPnoDest = $cal_dep->generateDocNo('DPSLIPNO',$lib);
				$deptslip_noDest = $arrSlipDPnoDest["SLIP_NO"];
				$lastdocument_noPayout = $arrSlipnoPayout["QUERY"]["LAST_DOCUMENTNO"] + 1;
				$updateDocuControlPayout = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLSLIPPAYOUT'");
				$updateDocuControlPayout->execute([':lastdocument_no' => $lastdocument_noPayout]);
				$lastdocument_noDest = $arrSlipDPnoDest["QUERY"]["LAST_DOCUMENTNO"] + 1;
				$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
				$updateDocuControl->execute([':lastdocument_no' => $lastdocument_noDest]);
				$getlastseq_noDest = $cal_dep->getLastSeqNo($deptaccount_no);
				$conoracle->beginTransaction();
				$slipPayout = $cal_loan->paySlipLonOut($conoracle,$config,$payoutslip_no,$member_no,'LWD',$lastdocument_noPayout,$dateOper,$dataCont["LOANTYPE_CODE"],
				$contract_no,$dataComing["amt_transfer"],$dataComing["payload"],$deptaccount_no,'TRN',null,$srcvcid["ACCOUNT_ID"],$log);
				if($slipPayout["RESULT"]){
					$receiveLon = $cal_loan->receiveLoanOD($conoracle,$config,$contract_no,$dataCont,null,$dataComing["amt_transfer"],
					$payoutslip_no,$ref_no,$deptaccount_no,0,$dataComing["payload"],'0.0.1',$dateOper,$log);
					if($receiveLon["RESULT"]){
						$depositMoney = $cal_dep->DepositMoneyInside($conoracle,$deptaccount_no,$destvcid["ACCOUNT_ID"],$itemtypeDeposit,
						$dataComing["amt_transfer"],0,$dateOper,$config,$log,$contract_no,$dataComing["payload"],$deptslip_noDest,$lib,
						$getlastseq_noDest["MAX_SEQ_NO"],$dataComing["menu_component"],null,null);
						if($depositMoney["RESULT"]){
							$conoracle->commit();
							$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																			,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																			coop_slip_no,id_userlogin,ref_no_source)
																			VALUES(:ref_no,'DAP',:from_account,:destination,'4',:amount,:penalty_amt,:amount_receive,'-1',
																			:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
							if($insertTransactionLog->execute([
								':ref_no' => $ref_no,
								':from_account' => $contract_no,
								':destination' => $deptaccount_no,
								':amount' => $dataComing["amt_transfer"],
								':penalty_amt' => 0,
								':amount_receive' => $dataComing["amt_transfer"],
								':operate_date' => $dateOper,
								':member_no' => $dataComing["payload"]["member_no"],
								':slip_no' => $payoutslip_no,
								':id_userlogin' => $dataComing["payload"]["id_userlogin"]
							])){
								$insertIntoInfo = $conmysql->prepare("UPDATE gcreceiveloanod SET receive_stauts =:is_status WHERE member_no = :member_no AND id_receiveloan = :id_receiveloan");
								if($insertIntoInfo->execute([
									':is_status' => $dataComing["is_status"],
									':member_no' => $dataComing["member_no"], 
									':id_receiveloan' => $dataComing["id_receiveloan"]
								])){
									$arrayResult['RESULT'] = TRUE;
								}else{
									$arrayResult['RESPONSE_MESSAGE'] = "ไม่สำเร็จ";
									$arrayResult['RESULT'] = FALSE;
									require_once('../../../../include/exit_footer.php');
								}
							}else{
								$arrayResult['RESPONSE_MESSAGE'] = "ไม่สำเร็จ";
								$arrayResult['RESULT'] = FALSE;
								require_once('../../../../include/exit_footer.php');
							}
							$arrToken = $func->getFCMToken('person',$dataComing["payload"]["member_no"]);
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
										$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
										$updateSyncNoti->execute([
											':ref_slipno' => $deptslip_noDest,
											':deptaccount_no' => $deptaccount_no
										]);
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
										$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
										$updateSyncNoti->execute([
											':ref_slipno' => $deptslip_noDest,
											':deptaccount_no' => $deptaccount_no
										]);
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
							require_once('../../../../include/exit_footer.php');
						}else{
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $depositMoney["RESPONSE_CODE"];
							if($depositMoney["RESPONSE_CODE"] == "WS0056"){
								$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($depositMoney["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
							}else{
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							}
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = $slipPayout["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}
		}else{
			$insertIntoInfo = $conmysql->prepare("UPDATE gcreceiveloanod SET receive_stauts =:is_status WHERE member_no = :member_no AND id_receiveloan = :id_receiveloan");
			if($insertIntoInfo->execute([
				':is_status' => $dataComing["is_status"],
				':member_no' => $dataComing["member_no"], 
				':id_receiveloan' => $dataComing["id_receiveloan"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สำเร็จ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>