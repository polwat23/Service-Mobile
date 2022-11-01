<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
		$itemtypeDeposit = 'DTL';
		$dataCont = $cal_loan->getContstantLoanContract($contract_no);
		//file_put_contents('Msgresponse.txt', json_encode($dataCont,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
		$interest = $cal_loan->calculateIntArrAPI($contract_no,$dataComing["amt_transfer"]);
		if($dataComing["amt_transfer"] > $dataCont["WITHDRAWABLE_AMT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0093';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
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
		$slipPayout = $cal_loan->paySlipLonOut($conoracle,$config,$payoutslip_no,$member_no,'LWD',null,$dateOper,$dataCont["LOANTYPE_CODE"],
		$contract_no,$dataComing["amt_transfer"],$payload,$deptaccount_no,'TRN',null,$srcvcid["ACCOUNT_ID"],$log);
		if($slipPayout["RESULT"]){
			$receiveLon = $cal_loan->receiveLoanOD($conoracle,$config,$contract_no,$dataCont,null,$dataComing["amt_transfer"],
			$payoutslip_no,$ref_no,$deptaccount_no,0,$payload,$dataComing["app_version"],$dateOper,$log);
			if($receiveLon["RESULT"]){
				$depositMoney = $cal_dep->DepositMoneyInside($conoracle,$deptaccount_no,$destvcid["ACCOUNT_ID"],$itemtypeDeposit,
				$dataComing["amt_transfer"],0,$dateOper,$config,$log,$contract_no,$payload,$deptslip_noDest,$lib,
				$getlastseq_noDest["MAX_SEQ_NO"],$dataComing["menu_component"],null,null);
				if($depositMoney["RESULT"]){
					$conoracle->commit();
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	coop_slip_no,id_userlogin,ref_no_source)
																	VALUES(:ref_no,'DAP',:from_account,:destination,'4',:amount,:penalty_amt,:amount_receive,'-1',
																	:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':from_account' => $contract_no,
						':destination' => $deptaccount_no,
						':amount' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
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
					require_once('../../include/exit_footer.php');
				}else{
					$conoracle->rollback();
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
				$conoracle->rollback();
				$arrayResult['RESPONSE_CODE'] = $receiveLon["RESPONSE_CODE"];
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
