<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','penalty_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$ref_no = date('YmdHis').substr($from_account_no,-3);
		$amount_receive = $dataComing["amt_transfer"] - $dataComing["penalty_amt"];
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		try {
			$argumentWS = [
				"as_wspass" => $config["WS_STRC_DB"],
				"as_src_deptaccount_no" => $from_account_no,
				"as_dest_deptaccount_no" => $to_account_no,
				"adtm_operate" => $dateOperC,
				"as_wslipitem_code" => "WTX",
				"as_dslipitem_code" => "DTX",
				"adc_amt" => $dataComing["amt_transfer"],
				"adc_fee" => $dataComing["penalty_amt"]
			];
			$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
			$slip_no = $resultWS->of_withdraw_deposit_transResult;
			$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no and deptaccount_no = :deptaccount_no");
			$fetchSeqno->execute([
				':deptslip_no' => $slip_no,
				':deptaccount_no' => $from_account_no
			]);
			$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
			$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
												VALUES(:remark,:deptaccount_no,:seq_no)");
			$insertRemark->execute([
				':remark' => $dataComing["remark"],
				':deptaccount_no' => $from_account_no,
				':seq_no' => $rowSeqno["SEQ_NO"]
			]);
			$arrayResult['TRANSACTION_NO'] = $ref_no;
			$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,:amount_receive,'-1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amount_receive,
				':operate_date' => $dateOper,
				':member_no' => $payload["member_no"],
				':slip_no' => $slip_no,
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$arrToken = $func->getFCMToken('person',$payload["member_no"]);
			$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
			foreach($arrToken["LIST_SEND"] as $dest){
				if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
					$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
						$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno and deptaccount_no = :deptaccount_no");
						$updateSyncNoti->execute([
							':ref_slipno' => $slip_no,
							':deptaccount_no' => $from_account_no
						]);
					}
				}
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}catch(SoapFault $e){
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '2',
					':destination' => $to_account_no,
					':response_code' => "WS0064",
					':response_message' => $e->getMessage()
				];
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '1',
					':destination' => $to_account_no,
					':response_code' => "WS0064",
					':response_message' => $e->getMessage()
				];
			}
			$log->writeLog('transferinside',$arrayStruc);
			$arrayResult["RESPONSE_CODE"] = 'WS0064';
			if(strpos($e->getMessage(),'จำนวนเงินฝากขั้นต่ำ') === FALSE){
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = preg_replace('/[\/"]/', '', $e->getMessage());
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
