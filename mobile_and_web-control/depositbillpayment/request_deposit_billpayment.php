<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['amt_transfer'],$dataComing)){
	$transaction_no = $dataComing["tran_id"];
	$etn_ref = $dataComing["bank_ref"];
	$cmd_operate = substr($dataComing["coop_account_no"],0,2);
	$coop_account_no = preg_replace('/-/','',substr($dataComing["coop_account_no"],2));
	$time = time();
	$fee_amt = 0;
	$dateOperC = date('c');
	$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
	$ref_no = time().$lib->randomText('all',3);
	$amt_transfer = $dataComing["amt_transfer"];
	if($cmd_operate == '01'){
		$depositItemtype = "DTE";
		$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$fetchDepttype->execute([':deptaccount_no' => $coop_account_no]);
		$rowDataDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC);
		$arrayGroup = array();
		$arrayGroup["account_id"] = null;
		$arrayGroup["action_status"] = "1";
		$arrayGroup["atm_no"] = "MOBILE";
		$arrayGroup["atm_seqno"] = null;
		$arrayGroup["aviable_amt"] = null;
		$arrayGroup["bank_accid"] = null;
		$arrayGroup["bank_cd"] = null;
		$arrayGroup["branch_cd"] = null;
		$arrayGroup["coop_code"] = $config["COOP_KEY"];
		$arrayGroup["coop_id"] = $config["COOP_ID"];
		$arrayGroup["deptaccount_no"] = $coop_account_no;
		$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
		$arrayGroup["entry_id"] = "MOBILE";
		$arrayGroup["fee_amt"] = $fee_amt;
		$arrayGroup["feeinclude_status"] = "1";
		$arrayGroup["item_amt"] = $amt_transfer;
		$arrayGroup["member_no"] = $dataComing["member_no"];
		$arrayGroup["moneytype_code"] = "CBT";
		$arrayGroup["msg_output"] = null;
		$arrayGroup["msg_status"] = null;
		$arrayGroup["operate_date"] = $dateOperC;
		$arrayGroup["oprate_cd"] = "003";
		$arrayGroup["post_status"] = "1";
		$arrayGroup["principal_amt"] = null;
		$arrayGroup["ref_slipno"] = null;
		$arrayGroup["slipitemtype_code"] = $depositItemtype;
		$arrayGroup["stmtitemtype_code"] = $depositItemtype;
		$arrayGroup["system_cd"] = "02";
		$arrayGroup["withdrawable_amt"] = null;
		$ref_slipno = null;
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl",array(
				'keep_alive' => false,
				'connection_timeout' => 900
			));
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_servResult;
				if($responseSoap->msg_status == '0000'){
					$ref_slipno = $responseSoap->ref_slipno;
					$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
					$updateSyncNoti->execute([
						':ref_slipno' => $ref_slipno,
						':deptaccount_no' => $coop_account_no
					]);
					$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no and deptaccount_no = :deptaccount_no");
					$fetchSeqno->execute([
						':deptslip_no' => $ref_slipno,
						':deptaccount_no' => $coop_account_no
					]);
					$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $coop_account_no,
						':seq_no' => $rowSeqno["SEQ_NO"]
					]);
					$arrExecute = [
						':ref_no' => $ref_no,
						':itemtype' => $depositItemtype,
						':from_account' => $coop_account_no,
						':destination' => $coop_account_no,
						':amount' => $dataComing["amt_transfer"],
						':fee_amt' => $fee_amt,
						':amount_receive' => $amt_transfer,
						':operate_date' => $dateOper,
						':member_no' => $dataComing["member_no"],
						':ref_no1' => $coop_account_no,
						':slip_no' => $ref_slipno,
						':etn_ref' => $etn_ref,
						':ref_no_source' => $transaction_no,
						':bank_code' => $dataComing["bank_code"]
					];
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
																,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																ref_no_1,coop_slip_no,etn_refno,ref_no_source,bank_code)
																VALUES(:ref_no,:itemtype,:from_account,'1',:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'1',:member_no,
																:ref_no1,:slip_no,:etn_ref,:ref_no_source,:bank_code)");
					if($insertTransactionLog->execute($arrExecute)){
					}else{
						$message_error = "ไม่สามารถ Insert ลงตาราง gctransaction ได้"."\n"."Query => ".$insertTransactionLog->queryString."\n".json_encode($arrExecute);
						$lib->sendLineNotify($message_error);
					}
					$arrToken = $func->getFCMToken('person',$dataComing["member_no"]);
					$templateMessage = $func->getTemplateSystem('BillPaymentDeposit',1);
					foreach($arrToken["LIST_SEND"] as $dest){
						if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
							$dataMerge = array();
							$dataMerge["COOP_ACCOUNT_NO"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
							$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
							$dataMerge["OPERATE_DATE"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
							$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
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
					$getNameMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname
														FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
														WHERE mb.member_no = :member_no");
					$getNameMember->execute([':member_no' => $dataComing["member_no"]]);
					$rowName = $getNameMember->fetch(PDO::FETCH_ASSOC);
					$arrayResult['RECEIVE_NAME'] = $rowName["PRENAME_SHORT"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
					$arrayResult['COOP_ACCOUNT_NO'] = $coop_account_no;
					$arrayResult['RESULT'] = TRUE;
					ob_flush();
					echo json_encode($arrayResult);
					exit();
					
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayStruc = [
						':member_no' => $dataComing["member_no"],
						':id_userlogin' => '5',
						':operate_date' => $dateOper,
						':sigma_key' => 'billpayment',
						':amt_transfer' => $amt_transfer,
						':response_code' => $responseSoap->msg_status,
						':response_message' => $responseSoap->msg_output
					];
					$log->writeLog('deposittrans',$arrayStruc);
					if(strpos($responseSoap->msg_output,'จำนวนเงินฝากขั้นต่ำ') === FALSE){
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = preg_replace('/[\/"]/', '', $responseSoap->msg_output);
						$arrayResult['RESULT'] = FALSE;
					}
					$arrayResult['RESPONSE_MESSAGE_SOURCE'] = preg_replace('/[\/"]/', '', $responseSoap->msg_output);
					ob_flush();
					echo json_encode($arrayResult);
					exit();
					
				}
			}catch(SoapFault $e){
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				$arrayStruc = [
					':member_no' => $dataComing["member_no"],
					':id_userlogin' => null,
					':operate_date' => $dateOper,
					':sigma_key' => 'billpayment',
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $e->getMessage()
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$message_error = "ไม่สามารถฝากเงินได้ สาเหตุเพราะ ".$e->getMessage();
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESPONSE_MESSAGE_SOURCE'] = preg_replace('/[\/"]/', '', $e->getMessage());
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
				
			}
		}catch(Throwable $e) {
			$arrayResult["RESPONSE_CODE"] = 'WS9999';
			$arrayStruc = [
				':member_no' => $dataComing["member_no"],
				':id_userlogin' => null,
				':operate_date' => $dateOper,
				':sigma_key' => 'billpayment',
				':amt_transfer' => $amt_transfer,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $e->getMessage()
			];
			$log->writeLog('deposittrans',$arrayStruc);
			$message_error = "ไม่สามารถฝากเงินได้ สาเหตุเพราะ ".$e->getMessage();
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESPONSE_MESSAGE_SOURCE'] = preg_replace('/[\/"]/', '', $e->getMessage());
			$arrayResult['RESULT'] = FALSE;
			ob_flush();
			echo json_encode($arrayResult);
			exit();
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0096";
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