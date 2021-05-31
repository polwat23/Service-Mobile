<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','slip_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PayMonthlyFull')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$from_account_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$amt_transfer = $dataComing["amt_transfer"];
		$dateOper = date('c');
		$penalty_amt = 0;
		$dateOperC = date('Y-m-d H:i:s',strtotime($dateOper));
		$arrInitDep = $cal_dep->initDept($from_account_no,$dataComing["amt_transfer"],'WFS');
		$constFromAcc = $cal_dep->getConstantAcc($from_account_no);
		$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
		if($arrInitDep["RESULT"]){
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($from_account_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
			if($arrRightDep["RESULT"]){
				if(isset($arrInitDep["PENALTY_AMT"]) && $arrInitDep["PENALTY_AMT"] > 0){
					$penalty_amt = $arrInitDep["PENALTY_AMT"];
				}
				$ref_no = time().$lib->randomText('all',3);
				$getReceiveAmt = $conoracle->prepare("SELECT RECEIVE_AMT FROM kptempreceive  
													WHERE kpslip_no = :kpslip_no and keeping_status = '1'");
				$getReceiveAmt->execute([':kpslip_no' => $dataComing['slip_no']]);
				$rowReceiveAmt = $getReceiveAmt->fetch(PDO::FETCH_ASSOC);
				$getlastseq_no = $cal_dep->getLastSeqNo($from_account_no);
				$arrSlipDPno = $cal_dep->generateDocNo('DPSLIPNO',$lib);
				$arrSeqDPSlipNoDest = array();
				$deptslip_no = $arrSlipDPno["SLIP_NO"];
				$lastdocument_noDP = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 1;
				$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
				$updateDocuControl->execute([':lastdocument_no' => $lastdocument_noDP]);
				$getNumberOfRecv = $conoracle->prepare("SELECT 
														kut.KEEPITEMTYPE_GRP,
														kpd.SEQ_NO
														FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
														kpd.keepitemtype_code = kut.keepitemtype_code
														WHERE kpd.kpslip_no = :kpslip_no and kut.KEEPITEMTYPE_GRP = 'DEP'
														ORDER BY kpd.SEQ_NO ASC");
				$getNumberOfRecv->execute([':kpslip_no' => $dataComing["slip_no"]]);
				while($rowNumberOfRecv = $getNumberOfRecv->fetch(PDO::FETCH_ASSOC)){
					$arrSlipDPnoDest = $cal_dep->generateDocNo('DPSLIPNO',$lib);
					$arrSeqDPSlipNoDest[$rowNumberOfRecv["SEQ_NO"]] = $arrSlipDPnoDest["SLIP_NO"];
					$lastdocument_noDPDest = $arrSlipDPnoDest["QUERY"]["LAST_DOCUMENTNO"] + 1;
					$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
					$updateDocuControl->execute([':lastdocument_no' => $lastdocument_noDPDest]);
				}
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
				$conmysql->beginTransaction();
				$cancelSlipMonthly = $conoracle->prepare("UPDATE kptempreceive SET keeping_status = '-99' WHERE kpslip_no = :slip_no");
				$cancelSlipMonthly->execute([':slip_no' => $dataComing["slip_no"]]);
				$wtdResult = $cal_dep->WithdrawMoneyInside($conoracle,$from_account_no,null,'WTM',$rowReceiveAmt["RECEIVE_AMT"],$penalty_amt,
				$dateOperC,$config,$log,$payload,$deptslip_no,$lib,$getlastseq_no["MAX_SEQ_NO"],$constFromAcc);
				if($wtdResult["RESULT"]){
					$paykeeping = $cal_loan->paySlip($conoracle,$rowReceiveAmt["RECEIVE_AMT"],$config,$payinslipdoc_no,$dateOperC,
					$srcvcid["ACCOUNT_ID"],$wtdResult["DEPTSLIP_NO"],$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,'WTM',$conmysql);
					if($paykeeping["RESULT"]){
						$getPaymentDetail = $conoracle->prepare("SELECT 
																kut.KEEPITEMTYPE_GRP,
																NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																KPD.SHRLONTYPE_CODE,
																CASE 
																WHEN kut.KEEPITEMTYPE_GRP = 'SHR' THEN
																kpd.MEMBER_NO
																WHEN kut.KEEPITEMTYPE_GRP = 'LON' THEN
																kpd.LOANCONTRACT_NO
																WHEN kut.KEEPITEMTYPE_GRP = 'DEP' THEN
																kpd.DESCRIPTION
																END as DESTINATION,
																kut.KEEPITEMTYPE_CODE,
																kut.KEEPITEMTYPE_DESC,
																kpd.SEQ_NO,
																ROW_NUMBER() OVER (PARTITION BY kut.KEEPITEMTYPE_GRP ORDER BY kpd.SEQ_NO) AS SLIP_SEQ_NO
																FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																kpd.keepitemtype_code = kut.keepitemtype_code
																LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																WHERE kpd.kpslip_no = :kpslip_no and kut.SIGN_FLAG = 1
																ORDER BY kpd.SEQ_NO ASC");
						$getPaymentDetail->execute([
							':kpslip_no' => $dataComing["slip_no"]
						]);
						while($rowKPDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
							$cancelSlipLoan = $conoracle->prepare("UPDATE kptempreceivedet SET keepitem_status = '-99' 
																	WHERE kpslip_no = :kpslip_no and seq_no = :seq_no");
							if($cancelSlipLoan->execute([
								':kpslip_no' => $dataComing["slip_no"],
								':seq_no' => $rowKPDetail["SEQ_NO"]
							])){
								if($rowKPDetail["KEEPITEMTYPE_GRP"] == 'LON'){
									$dataCont = $cal_loan->getContstantLoanContract($rowKPDetail["DESTINATION"]);
									$int_return = $dataCont["INTEREST_RETURN"];
									if($rowKPDetail["ITEM_PAYMENT"] > $dataCont["INTEREST_ARREAR"]){
										$intarrear = $dataCont["INTEREST_ARREAR"];
									}else{
										$intarrear = $rowKPDetail["ITEM_PAYMENT"];
									}
									$int_returnSrc = 0;
									$int_returnFull = 0;
									$interest = $cal_loan->calculateInterest($rowKPDetail["DESTINATION"],$rowKPDetail["ITEM_PAYMENT"]);
									$interestFull = $interest;
									$interestPeriod = $interest - $dataCont["INTEREST_ARREAR"];
									if($interestPeriod < 0){
										$interestPeriod = 0;
									}
									if($interest > 0){
										if($rowKPDetail["ITEM_PAYMENT"] < $interest){
											$interest = $rowKPDetail["ITEM_PAYMENT"];
										}else{
											$prinPay = $rowKPDetail["ITEM_PAYMENT"] - $interest;
										}
										if($prinPay < 0){
											$prinPay = 0;
										}
									}else{
										$prinPay = $rowKPDetail["ITEM_PAYMENT"];
									}
									if($dataCont["CHECK_KEEPING"] == '0'){
										if($dataCont["SPACE_KEEPING"] != 0){
											$int_returnSrc = 0;
											$int_returnFull = $int_returnSrc;
										}
									}
									$paykeepingdet = $cal_loan->paySlipLonDet($conoracle,$dataCont,$rowKPDetail["ITEM_PAYMENT"],$config,$dateOperC,$log,$payload,
									$from_account_no,$payinslip_no,'LON',$rowKPDetail["SHRLONTYPE_CODE"],$rowKPDetail["DESTINATION"],$prinPay,$interest,
									$intarrear,$int_returnSrc,$interestPeriod,$rowKPDetail["SLIP_SEQ_NO"]);
									if($paykeepingdet["RESULT"]){
										$ref_noLN = time().$lib->randomText('all',3);
										$repayloan = $cal_loan->repayLoan($conoracle,$rowKPDetail["DESTINATION"],$rowKPDetail["ITEM_PAYMENT"],0,$config,$payinslipdoc_no,$dateOperC,
										$srcvcid["ACCOUNT_ID"],$wtdResult["DEPTSLIP_NO"],$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_noLN,$dataComing["app_version"]);
										if($repayloan["RESULT"]){
										}else{
											$conoracle->rollback();
											$conmysql->rollback();
											$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
										}
									}else{
										$conoracle->rollback();
										$conmysql->rollback();
										$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
									}
								}else if($rowKPDetail["KEEPITEMTYPE_GRP"] == 'SHR'){
									$paykeepingdet = $cal_loan->paySlipDet($conoracle,$rowKPDetail["ITEM_PAYMENT"],$config,$dateOperC,$log,$payload,
									$from_account_no,$payinslip_no,'SHR',$rowKPDetail["SHRLONTYPE_CODE"],'ค่าหุ้นรายเดือน',$rowKPDetail["SLIP_SEQ_NO"]);
									if($paykeepingdet["RESULT"]){
										$buyshare = $cal_share->buyShare($conoracle,$rowKPDetail["DESTINATION"],$rowKPDetail["ITEM_PAYMENT"],0,$config,$payinslipdoc_no,$dateOperC,
										$srcvcid["ACCOUNT_ID"],$wtdResult["DEPTSLIP_NO"],$log,$lib,$payload,$from_account_no,$payinslip_no,$ref_no);
										if($buyshare["RESULT"]){
										}else{
											$conoracle->rollback();
											$conmysql->rollback();
											$arrayResult['RESPONSE_CODE'] = $buyshare["RESPONSE_CODE"];
											if(isset($configError["BUY_SHARES_ERR"][0][$buyshare["SHARE_ERR"]][0][$lang_locale])){
												$arrayResult['RESPONSE_MESSAGE'] = str_replace('${'.$buyshare["TYPE_ERR"].'}',number_format($buyshare["AMOUNT_ERR"],2),$configError["BUY_SHARES_ERR"][0][$buyshare["SHARE_ERR"]][0][$lang_locale]);
											}else{
												$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											}
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
										}
									}else{
										$conoracle->rollback();
										$conmysql->rollback();
										$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
									}
								}else if($rowKPDetail["KEEPITEMTYPE_GRP"] == 'DEP'){
									$getlastseq_noDest = $cal_dep->getLastSeqNo($rowKPDetail["DESTINATION"]);
									$depositMoney = $cal_dep->DepositMoneyInside($conoracle,$rowKPDetail["DESTINATION"],$srcvcid["ACCOUNT_ID"],'DTM',$rowKPDetail["ITEM_PAYMENT"],0,$dateOperC,$config,
									$log,$from_account_no,$payload,$arrSeqDPSlipNoDest[$rowKPDetail["SEQ_NO"]],$lib,$getlastseq_noDest["MAX_SEQ_NO"],$dataComing["menu_component"],$wtdResult["DEPTSLIP_NO"]);
									if($depositMoney["RESULT"]){
									}else{
										$conoracle->rollback();
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
									$paykeepingdet = $cal_loan->paySlipDet($conoracle,$rowKPDetail["ITEM_PAYMENT"],$config,$dateOperC,$log,$payload,
									$from_account_no,$payinslip_no,$rowKPDetail["KEEPITEMTYPE_CODE"],$rowKPDetail["SHRLONTYPE_CODE"],$rowKPDetail["KEEPITEMTYPE_DESC"],$rowKPDetail["SLIP_SEQ_NO"]);
									if($paykeepingdet["RESULT"]){
									}else{
										$conoracle->rollback();
										$conmysql->rollback();
										$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
									}
								}
							}else{
								$conoracle->rollback();
								$conmysql->rollback();
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOperC,
									':deptaccount_no' => $from_account_no,
									':amt_transfer' => $rowKPDetail["ITEM_PAYMENT"],
									':status_flag' => '0',
									':destination' => $rowKPDetail["DESTINATION"],
									':response_code' => "WS0066",
									':response_message' => 'cancel kpslip ไม่ได้'.$cancelSlipLoan->queryString.json_encode([
										':kpslip_no' => $dataComing["slip_no"],
										':seq_no' => $rowKPDetail["SEQ_NO"]
									])
								];
								$log->writeLog('repayloan',$arrayStruc);
								$arrayResult["RESPONSE_CODE"] = 'WS0066';
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,
																	destination,transfer_mode
																	,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	coop_slip_no,id_userlogin,ref_no_source)
																	VALUES(:ref_no,:slip_type,:from_account,'3',:destination,'2',:amount,:penalty_amt,
																	:amount_receive,'-1',:operate_date,'1',:member_no,:coop_slip_no,:id_userlogin,:coop_slip_no)");
						$insertTransactionLog->execute([
							':ref_no' => $ref_no,
							':slip_type' => 'WTM',
							':from_account' => $from_account_no,
							':destination' => $payinslip_no,
							':amount' => $dataComing["amt_transfer"],
							':penalty_amt' => $penalty_amt,
							':amount_receive' => $dataComing["amt_transfer"] - $penalty_amt,
							':operate_date' => $dateOperC,
							':member_no' => $payload["member_no"],
							':coop_slip_no' => $wtdResult["DEPTSLIP_NO"],
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$conoracle->commit();
						$conmysql->commit();
						$arrToken = $func->getFCMToken('person',$payload["member_no"]);
						$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
						$dataMerge = array();
						$dataMerge["PAYINSLIP_NO"] = $payinslip_no;
						$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
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
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = $paykeeping["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$conoracle->rollback();
					$conmysql->rollback();
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
						':amt_transfer' => $rowKPDetail["ITEM_PAYMENT"],
						':status_flag' => '0',
						':destination' => $contract_no,
						':response_code' => "WS0066",
						':response_message' => $wtdResult["ACTION"]
					];
					$log->writeLog('repayloan',$arrayStruc);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
				if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
			if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
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