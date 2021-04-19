<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['tran_id'],$dataComing)){
	$lang_locale = 'en';
	$checkBillAvailable = $conmysql->prepare("SELECT transfer_status,expire_date,id_userlogin,app_version,member_no
											FROM gcqrcodegenmaster 
											WHERE qrgenerate = :tran_id and member_no = :member_no");
	$checkBillAvailable->execute([
		':tran_id' => $dataComing["tran_id"],
		':member_no' => $dataComing["member_no"]
	]);
	if($checkBillAvailable->rowCount() > 0){
		$rowCheckBill = $checkBillAvailable->fetch(PDO::FETCH_ASSOC);
		$fee_amt = 0;
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		if($rowCheckBill["transfer_status"] == '0'){
			if(date('YmdHis',strtotime($rowCheckBill["expire_date"])) > date('YmdHis')){
				$payload = array();
				$payload["member_no"] = $rowCheckBill["member_no"];
				$payload["id_userlogin"] = $rowCheckBill["id_userlogin"];
				$payload["app_version"] = $rowCheckBill["app_version"];
				$amt_tranferLon = $dataComing["amt_transfer"];
				$vccAccID = $func->getConstant('map_account_id_ktb');
				$getDetailTranDP = $conmysql->prepare("SELECT ref_account,qrtransferdt_amt FROM gcqrcodegendetail 
													WHERE qrgenerate = :tran_id and trans_code_qr = '01'");
				$getDetailTranDP->execute([':tran_id' => $dataComing["tran_id"]]);
				$arrDpSlip = array();
				while($rowDetailDP = $getDetailTranDP->fetch(PDO::FETCH_ASSOC)){
					$amt_transferLon -= $rowDetailDP["qrtransferdt_amt"];
					$arrSlipDPnoDest = $cal_dep->generateDocNo('DPSLIPNO',$lib);
					$arrDpSlip[$rowDetailDP["ref_account"]] = $arrSlipDPnoDest["SLIP_NO"];
					$lastdocument_noDest = $arrSlipDPnoDest["QUERY"]["LAST_DOCUMENTNO"] + 1;
					$updateDocuControl = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'DPSLIPNO'");
					$updateDocuControl->execute([':lastdocument_no' => $lastdocument_noDest]);
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
				$paykeeping = $cal_loan->paySlip($conoracle,$amt_transferLon,$config,$payinslipdoc_no,$dateOper,
				$vccAccID,null,$log,$lib,$payload,$dataComing["bank_ref"],$payinslip_no,$dataComing["member_no"],$ref_no,
				'WFS',$conmysql,0,'006');
				$getDetailTran = $conmysql->prepare("SELECT trans_code_qr,ref_account,qrtransferdt_amt,
													ROW_NUMBER() OVER (PARTITION BY trans_code_qr ORDER BY ref_account) as seq_no
													FROM gcqrcodegendetail 
													WHERE qrgenerate = :tran_id");
				$getDetailTran->execute([':tran_id' => $dataComing["tran_id"]]);
				while($rowDetail = $getDetailTran->fetch(PDO::FETCH_ASSOC)){
					$ref_no = time().$lib->randomText('all',3);
					if($rowDetail["trans_code_qr"] == '01'){ //ฝากเงิน
						$deptaccount_no = preg_replace('/-/','',$rowDetail["ref_account"]);
						$getlastseq_noDest = $cal_dep->getLastSeqNo($deptaccount_no);
						$depositMoney = $cal_dep->DepositMoneyInside($conoracle,$deptaccount_no,$vccAccID,'DTE',
						$rowDetail["qrtransferdt_amt"],0,$dateOper,$config,$log,$dataComing["bank_ref"],$payload,
						$arrDpSlip[$rowDetail["ref_account"]],$lib,$getlastseq_noDest["MAX_SEQ_NO"],"TransactionDeposit",null,'006');
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
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}else if($rowDetail["trans_code_qr"] == '02'){ //ชำระหนี้
						$dataCont = $cal_loan->getContstantLoanContract($rowDetail["ref_account"]);
						$int_return = $dataCont["INTEREST_RETURN"];
						if($rowDetail["qrtransferdt_amt"] > $dataCont["INTEREST_ARREAR"]){
							$intarrear = $dataCont["INTEREST_ARREAR"];
						}else{
							$intarrear = $rowDetail["qrtransferdt_amt"];
						}
						$int_returnSrc = 0;
						$int_returnFull = 0;
						$interest = $cal_loan->calculateInterest($rowDetail["ref_account"],$rowDetail["qrtransferdt_amt"]);
						$interestFull = $interest;
						$interestPeriod = $interest - $dataCont["INTEREST_ARREAR"];
						if($interestPeriod < 0){
							$interestPeriod = 0;
						}
						if($interest > 0){
							if($rowDetail["qrtransferdt_amt"] < $interest){
								$interest = $rowDetail["qrtransferdt_amt"];
							}else{
								$prinPay = $rowDetail["qrtransferdt_amt"] - $interest;
							}
							if($prinPay < 0){
								$prinPay = 0;
							}
						}else{
							$prinPay = $rowDetail["qrtransferdt_amt"];
						}
						if($dataCont["CHECK_KEEPING"] == '0'){
							if($dataCont["SPACE_KEEPING"] != 0){
								$int_returnSrc = 0;
								$int_returnFull = $int_returnSrc;
							}
						}
						$paykeepingdet = $cal_loan->paySlipLonDet($conoracle,$dataCont,$rowDetail["qrtransferdt_amt"],$config,$dateOper,$log,$payload,
						$dataComing["bank_ref"],$payinslip_no,'LON',$dataCont["LOANTYPE_CODE"],$rowDetail["ref_account"],$prinPay,$interest,
						$intarrear,$int_returnSrc,$interestPeriod,$rowDetail["seq_no"]);
						if($paykeepingdet["RESULT"]){
							$ref_noLN = time().$lib->randomText('all',3);
							$repayloan = $cal_loan->repayLoan($conoracle,$rowDetail["ref_account"],$rowDetail["qrtransferdt_amt"],0,$config,$payinslipdoc_no,$dateOper,
							$vccAccID,null,$log,$lib,$payload,$dataComing["bank_ref"],$payinslip_no,$dataComing["member_no"],$ref_noLN,$payload["app_version"]);
							if($repayloan["RESULT"]){
							}else{
								$conoracle->rollback();
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								ob_flush();
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$conoracle->rollback();
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = $paykeepingdet["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$conoracle->rollback();
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS0096";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,:slip_type,:from_account,:destination,'5',:amount,:penalty_amt,
															:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:source_no)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':slip_type' => 'DTE',
					':from_account' => $dataComing["bank_ref"],
					':destination' => $dataComing["tran_id"],
					':amount' => $dataComing["amt_transfer"],
					':penalty_amt' => 0,
					':amount_receive' => $dataComing["qrtransferdt_amt"],
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':slip_no' => $payinslip_no,
					':source_no' => $dataComing["source_ref"],
					':id_userlogin' => $payload["id_userlogin"]
				]);
				$conoracle->commit();
				$conmysql->commit();
				$arrToken = $func->getFCMToken('person',$payload["member_no"]);
				$templateMessage = $func->getTemplateSystem("Billpayment",1);
				$dataMerge = array();
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
				$updateQRCodeMaster = $conmysql->prepare("UPDATE gcqrcodegenmaster SET transfer_status = '1' WHERE qrgenerate = :tran_id");
				$updateQRCodeMaster->execute([':tran_id' => $dataComing["tran_id"]]);
				$arrayResult['RESULT'] = TRUE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0109";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0108";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			ob_flush();
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0107";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
		$arrayResult['RESULT'] = FALSE;
		ob_flush();
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
	$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	ob_flush();
	echo json_encode($arrayResult);
	exit();
}
?>