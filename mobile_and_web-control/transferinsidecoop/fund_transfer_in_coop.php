<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','penalty_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$itemtypeWithdraw = 'WIM';
		$itemtypeDepositDest = 'DIM';
		$ref_no = time().$lib->randomText('all',3);
		$dateOper = date('c');
		$dateOperC = date('Y-m-d H:i:s',strtotime($dateOper));
		// Start-Withdraw
		$constFromAcc = $cal_dep->getConstantAcc($from_account_no);
		$constToAcc = $cal_dep->getConstantAcc($to_account_no);
		$srcvcid = $cal_dep->getVcMapID($constFromAcc["DEPTTYPE_CODE"]);
		$destvcid = $cal_dep->getVcMapID($constToAcc["DEPTTYPE_CODE"]);
		$checkSeqAmtSrc = $cal_dep->getSequestAmt($from_account_no,$itemtypeWithdraw);
		$checkSeqAmtDest = $cal_dep->getSequestAmt($to_account_no,$itemtypeDepositDest);
		if($checkSeqAmtSrc["CAN_WITHDRAW"] && $checkSeqAmtDest["CAN_DEPOSIT"]){
			if($constFromAcc["MINPRNCBAL"] > $constFromAcc["PRNCBAL"] - ($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"] + $dataComing["amt_transfer"])){
				$arrayResult['RESPONSE_CODE'] = "WS0091";
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}',number_format($checkSeqAmtSrc["SEQUEST_AMOUNT"] + $constFromAcc["CHECKPEND_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			$arrSlipDPno = $cal_dep->generateDocNo('ONLINETX',$lib);
			$deptslip_no = $arrSlipDPno["SLIP_NO"];
			$lastdocument_no = $arrSlipDPno["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControl = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETX'");
			$updateDocuControl->execute([':lastdocument_no' => $lastdocument_no]);
			$getlastseq_no = $cal_dep->getLastSeqNo($from_account_no);
			$getlastseq_noDest = $cal_dep->getLastSeqNo($to_account_no);
			$arrSlipDPnoDest = $cal_dep->generateDocNo('ONLINETX',$lib);
			$deptslip_noDest = $arrSlipDPnoDest["SLIP_NO"];
			$lastdocument_noDest = $arrSlipDPnoDest["QUERY"]["LAST_DOCUMENTNO"] + 1;
			$updateDocuControl = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETX'");
			$updateDocuControl->execute([':lastdocument_no' => $lastdocument_noDest]);
			$conmssql->beginTransaction();
			$wtdResult = $cal_dep->WithdrawMoneyInside($conmssql,$from_account_no,$destvcid["ACCOUNT_ID"],$itemtypeWithdraw,$dataComing["amt_transfer"],
			$dataComing["penalty_amt"],$dateOperC,$config,$log,$payload,$deptslip_no,$lib,$getlastseq_no["MAX_SEQ_NO"],$constFromAcc);
			if($wtdResult["RESULT"]){
				if($dataComing["penalty_amt"] > 0){
					$constFromAcc["PRNCBAL"] = $constFromAcc["PRNCBAL"] - $dataComing["amt_transfer"];
					$constFromAcc["WITHDRAWABLE_AMT"] = $constFromAcc["WITHDRAWABLE_AMT"] - $dataComing["amt_transfer"];
					$vccamtPenalty = $cal_dep->getVcMapID('00','DEP','FEE');
					$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
					$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
					$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
					$updateDocuControlFee = $conmssql->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
					$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
					$penaltyWtd = $cal_dep->insertFeeTransaction($conmssql,$from_account_no,$vccamtPenalty["ACCOUNT_ID"],'FMB',
					$dataComing["amt_transfer"],$dataComing["penalty_amt"],$dateOperC,$config,$wtdResult["DEPTSLIP_NO"],$lib,$wtdResult["MAX_SEQNO"],$constFromAcc,false,null,null,$deptslip_noFee);
					if($penaltyWtd["RESULT"]){
						
					}else{
						$arrayResult['RESPONSE_CODE'] = $penaltyWtd["RESPONSE_CODE"];
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOperC,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':penalty_amt' => $dataComing["penalty_amt"],
								':type_request' => '2',
								':transfer_flag' => '2',
								':destination' => $to_account_no,
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => $penaltyWtd['ACTION']
							];
						}else{
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOperC,
								':deptaccount_no' => $from_account_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':penalty_amt' => $dataComing["penalty_amt"],
								':type_request' => '2',
								':transfer_flag' => '1',
								':destination' => $to_account_no,
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => $penaltyWtd['ACTION']
							];
						}
						$log->writeLog('transferinside',$arrayStruc);
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}
				$depositMoney = $cal_dep->DepositMoneyInside($conmssql,$to_account_no,$srcvcid["ACCOUNT_ID"],$itemtypeDepositDest,
				$dataComing["amt_transfer"],0,$dateOperC,$config,$log,$from_account_no,$payload,$deptslip_noDest,$lib,
				$getlastseq_noDest["MAX_SEQ_NO"],$dataComing["menu_component"],$ref_no,true,$wtdResult["DEPTSLIP_NO"]);
				if($depositMoney["RESULT"]){
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $from_account_no,
						':seq_no' => $getlastseq_no["MAX_SEQ_NO"] + 1
					]);
					$arrToken = $func->getFCMToken('person',$payload["member_no"]);
					$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
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
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																coop_slip_no,id_userlogin,ref_no_source)
																VALUES(:ref_no,:slip_type,:from_account,:destination,'1',:amount,:penalty_amt,
																:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
					$insertTransactionLog->execute([
						':ref_no' => $ref_no,
						':slip_type' => $itemtypeWithdraw,
						':from_account' => $from_account_no,
						':destination' => $to_account_no,
						':amount' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':amount_receive' => $dataComing["amt_transfer"],
						':operate_date' => $dateOperC,
						':member_no' => $payload["member_no"],
						':slip_no' => $deptslip_no,
						':id_userlogin' => $payload["id_userlogin"]
					]);
					if($payload["member_no"] != $constToAcc["MEMBER_NO"]){
						$arrToken = $func->getFCMToken('person', $constToAcc["MEMBER_NO"]);
						$templateMessage = $func->getTemplateSystem('DestinationReceive',1);
						$dataMerge = array();
						$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($to_account_no,$func->getConstant('hidden_dep'));
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
								$arrPayloadNotify["SEND_BY"] = 'system';
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
								if($func->insertHistory($arrPayloadNotify,'2')){
									$lib->sendNotifyHW($arrPayloadNotify,"person");
								}
							}
						}
					}
					$conmssql->commit();
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmssql->rollback();
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
				$conmssql->rollback();
				$arrayResult['RESPONSE_CODE'] = $wtdResult["RESPONSE_CODE"];
				if($wtdResult["RESPONSE_CODE"] == 'WS0091'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${sequest_amt}',number_format($wtdResult["SEQUEST_AMOUNT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOperC,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':type_request' => '2',
						':transfer_flag' => '2',
						':destination' => $to_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $wtdResult['ACTION']
					];
				}else{
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOperC,
						':deptaccount_no' => $from_account_no,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':type_request' => '2',
						':transfer_flag' => '1',
						':destination' => $to_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $wtdResult['ACTION']
					];
				}
				$log->writeLog('transferinside',$arrayStruc);
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