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
				$ref_no = time().$lib->randomText('all',3);
				$have_dep = FALSE;
				$payload = array();
				$payload["member_no"] = $rowCheckBill["member_no"];
				$payload["id_userlogin"] = $rowCheckBill["id_userlogin"];
				$payload["app_version"] = $rowCheckBill["app_version"];
				
				$conoracle->beginTransaction();
				$conmysql->beginTransaction();
				$getDetailTran = $conmysql->prepare("SELECT trans_code_qr,ref_account,qrtransferdt_amt,
													ROW_NUMBER() OVER (PARTITION BY trans_code_qr ORDER BY ref_account) as seq_no
													FROM gcqrcodegendetail 
													WHERE qrgenerate = :tran_id");
				$getDetailTran->execute([':tran_id' => $dataComing["tran_id"]]);
				while($rowDetail = $getDetailTran->fetch(PDO::FETCH_ASSOC)){
					if($rowDetail["trans_code_qr"] == '01'){ //ฝากเงิน
						$itemtypeDepositDest = 'T08';
						$deptaccount_no = preg_replace('/-/','',$rowDetail["ref_account"]);
						$depositMoney = $cal_dep->DepositMoneyInside($conoracle,$deptaccount_no,$itemtypeDepositDest,$rowDetail["qrtransferdt_amt"],
						$dataComing["tran_id"],$payload,$dataComing["menu_component"],$log,$ref_no,true);
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
						$arrSlipnoPayin = $cal_dep->generateDocNo('03',$lib);
						$arrSlipDocNoPayin = $cal_dep->generateDocNo('03',$lib);
						$payinslip_no = $arrSlipnoPayin["BILL_RUNNING"];
						$payinslipdoc_no = $arrSlipDocNoPayin["SLIP_NO"];
						$repayloan = $cal_loan->repayLoan($conoracle,$rowDetail["ref_account"],$rowDetail["qrtransferdt_amt"],0,
						$config,$payinslipdoc_no,$dateOperC,
						$log,$lib,$payload,$dataComing["tran_id"],$payinslip_no,$payload["member_no"],$ref_no,$payload["app_version"]);
						if($repayloan["RESULT"]){
						}else{
							$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
							$arrayResult['RESPONSE_MESSAGE'] = json_encode($repayloan);
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}else if($rowDetail["trans_code_qr"] == '03'){ //ชำระหุ้น
						$buyshare = $cal_shr->buyShare($conoracle,$rowDetail["ref_account"],$rowDetail["qrtransferdt_amt"],$config,$dateOperC,$log,$lib,$payload,$dataComing["tran_id"],$ref_no);
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
				$getPayAccFee = $conmysql->prepare("SELECT gba.account_payfee,cs.fee_deposit FROM gcbindaccount gba 
													LEFT JOIN csbankdisplay cs ON gba.bank_code = cs.bank_code
													WHERE gba.member_no = :member_no 
													and gba.bindaccount_status = '1' and gba.bank_code = '999'");
				$getPayAccFee->execute([':member_no' => $payload["member_no"]]);
				$rowPayFee = $getPayAccFee->fetch(PDO::FETCH_ASSOC);
				$penaltyWtd = $cal_dep->WithdrawMoneyInside($conoracle,$rowPayFee["account_payfee"],"W16",$rowPayFee["fee_deposit"],null,$ref_no);
				if($penaltyWtd["RESULT"]){
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																coop_slip_no,id_userlogin,ref_no_source)
																VALUES(:ref_no,:slip_type,:from_account,:destination,'5',:amount,:penalty_amt,
																:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:source_no)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':slip_type' => 'DTX',
						':from_account' => $dataComing["bank_ref"],
						':destination' => $dataComing["tran_id"],
						':amount' => $dataComing["amt_transfer"],
						':penalty_amt' => 0,
						':amount_receive' => $dataComing["amt_transfer"],
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
					$conoracle->rollback();
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0113";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					ob_flush();
					echo json_encode($arrayResult);
					exit();	
				}
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
