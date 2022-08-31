<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','contract_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$from_account_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$itemtypeWithdraw = 'WFS';
		$ref_no = time().$lib->randomText('all',3);
		$dateOper = date('c');
		$dateOperC = date('Y-m-d H:i:s',strtotime($dateOper));
		$dataCont = $cal_loan->getContstantLoanContract($dataComing["contract_no"]);
		
		$int_return = $dataCont["INTEREST_RETURN"];
		$prinPay = 0;
		$interestPeriod = 0;
		$withdrawStatus = FALSE;
		$intarrear = $dataCont["INTEREST_ARREAR"];
		$interest = $cal_loan->calculateIntAPI($dataComing["loancontract_no"],$dataComing["amt_transfer"]);
		$interestPeriod = $interest["INT_PERIOD"] - $dataCont["INTEREST_ARREAR"];
		if($interestPeriod < 0){
			$interestPeriod = 0;
		}
		$int_returnSrc = $interest["INT_RETURN"];
		$interestFull = $interest["INT_PERIOD"];
		if($interestFull > 0){
			if($dataComing["amt_transfer"] < $interestFull){
				$interestFull = $dataComing["amt_transfer"];
			}else{
				$prinPay = $dataComing["amt_transfer"] - $interestFull;
			}
			if($prinPay < 0){
				$prinPay = 0;
			}
		}else{
			$prinPay = $dataComing["amt_transfer"];
		}
		$constFromAcc = $cal_dep->getConstantAcc($from_account_no);
		$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
		$destvcid = $cal_dep->getVcMapID($dataCont["LOANTYPE_CODE"],'LON');
		$checkSeqAmtSrc = $cal_dep->getSequestAmt($from_account_no,$itemtypeWithdraw);
		if($checkSeqAmtSrc["CAN_WITHDRAW"]){
			if($constFromAcc["MINPRNCBAL"] > $constFromAcc["PRNCBAL"] - ($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"] + $dataComing["amt_transfer"])){
				$arrayResult['RESPONSE_CODE'] = "WS0091";
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}',number_format($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			$arrSlipDPno = $cal_dep->generateDocNo('DPSLIPNO',$lib);
			$deptslip_no = $arrSlipDPno["SLIP_NO"];
			if($dataComing["penalty_amt"] > 0){
				$lastdocument_no = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 2;
			}else{
				$lastdocument_no = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 1;
			}
			$getlastseq_no = $cal_dep->getLastSeqNo($from_account_no);
			$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
			$updateDocuControl->execute([':lastdocument_no' => $lastdocument_no]);
			$arrSlipnoPayin = $cal_dep->generateDocNo('SLSLIPPAYIN',$lib);
			$arrSlipDocNoPayin = $cal_dep->generateDocNo('SLRECEIPTNO',$lib);
			$payinslip_no = $arrSlipnoPayin["SLIP_NO"];
			$payinslipdoc_no = $arrSlipDocNoPayin["SLIP_NO"];
			$lastdocument_noPayin = $arrSlipnoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$lastdocument_noDocPayin = $arrSlipDocNoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControlPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLSLIPPAYIN'");
			$updateDocuControlPayin->execute([':lastdocument_no' => $lastdocument_noPayin]);
			$updateDocuControlDocPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'SLRECEIPTNO'");
			$updateDocuControlDocPayin->execute([':lastdocument_no' => $lastdocument_noDocPayin]);
			$conoracle->beginTransaction();
			$wtdResult = $cal_dep->WithdrawMoneyInside($conoracle,$from_account_no,$destvcid["ACCOUNT_ID"],$itemtypeWithdraw,$dataComing["amt_transfer"],
			$dataComing["penalty_amt"],$dateOperC,$config,$log,$payload,$deptslip_no,$lib,$getlastseq_no["MAX_SEQ_NO"],$constFromAcc);
			if($wtdResult["RESULT"]){
				$payslip = $cal_loan->paySlip($conoracle,$dataComing["amt_transfer"],$config,$payinslipdoc_no,$dateOperC,
				$srcvcid["ACCOUNT_ID"],$wtdResult["DEPTSLIP_NO"],$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$itemtypeWithdraw,$conmysql);
				if($payslip["RESULT"]){
					$payslipdet = $cal_loan->paySlipLonDet($conoracle,$dataCont,$dataComing["amt_transfer"],$config,$dateOperC,$log,$payload,
					$from_account_no,$payinslip_no,'LON',$dataCont["LOANTYPE_CODE"],$dataComing["contract_no"],$prinPay,$interestFull,
					0,$int_returnSrc,$interestPeriod,'1');
					if($payslipdet["RESULT"]){
						$repayloan = $cal_loan->repayLoan($conoracle,$dataComing["contract_no"],$dataComing["amt_transfer"],$dataComing["penalty_amt"],
						$config,$payinslipdoc_no,$dateOperC,
						$srcvcid["ACCOUNT_ID"],$wtdResult["DEPTSLIP_NO"],$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$dataComing["app_version"],$interestFull,$int_returnSrc);
						if($repayloan["RESULT"]){
							$conoracle->commit();
							$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
																VALUES(:remark,:deptaccount_no,:seq_no)");
							$insertRemark->execute([
								':remark' => $dataComing["remark"],
								':deptaccount_no' => $from_account_no,
								':seq_no' => $getlastseq_no["MAX_SEQ_NO"] + 1
							]);
							$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																		,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																		coop_slip_no,id_userlogin,ref_no_source)
																		VALUES(:ref_no,:slip_type,:from_account,:destination,'2',:amount,:penalty_amt,
																		:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
							$insertTransactionLog->execute([
								':ref_no' => $ref_no,
								':slip_type' => $itemtypeWithdraw,
								':from_account' => $from_account_no,
								':destination' => $dataComing["contract_no"],
								':amount' => $dataComing["amt_transfer"],
								':penalty_amt' => $dataComing["penalty_amt"],
								':amount_receive' => $dataComing["amt_transfer"] - $dataComing["penalty_amt"],
								':operate_date' => $dateOperC,
								':member_no' => $payload["member_no"],
								':slip_no' => $wtdResult["DEPTSLIP_NO"],
								':id_userlogin' => $payload["id_userlogin"]
							]);
							$arrToken = $func->getFCMToken('person',$payload["member_no"]);
							$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
							$dataMerge = array();
							$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
							$dataMerge["CONTRACT_NO"] = $dataComing["contract_no"];
							$dataMerge["AMOUNT"] = number_format($dataComing["amt_transfer"],2);
							$dataMerge["INT_PAY"] = number_format($interestFull,2);
							$dataMerge["PRIN_PAY"] = number_format($prinPay,2);
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
							$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOperC,'D m Y',true);
							$arrayResult['RESULT'] = TRUE;
							require_once('../../include/exit_footer.php');
						}else{
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE_CODE'] = $payslipdet["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = $payslip["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$conoracle->rollback();
				$arrayResult['RESPONSE_CODE'] = $wtdResult["RESPONSE_CODE"];
				if($wtdResult["RESPONSE_CODE"] == 'WS0091'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}',number_format($wtdResult["SEQUEST_AMOUNT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOperC,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':status_flag' => '0',
					':destination' => $dataComing["contract_no"],
					':response_code' => "WS0066",
					':response_message' => $wtdResult["ACTION"]
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0092";
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
