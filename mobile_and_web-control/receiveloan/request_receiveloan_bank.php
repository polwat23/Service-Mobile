<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','contract_no','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanReceive')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$contract_no = str_replace('/','',str_replace('.','',$dataComing["contract_no"]));
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$ref_no = date('YmdHis').substr($deptaccount_no,-3);
		
		$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
														,amount,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
														coop_slip_no,id_userlogin,ref_no_source)
														VALUES(:ref_no,'DAP',:from_account,:destination,'4',:amount,:penalty_amt,:amount_receive,'1',:operate_date,'1',:member_no,:slip_no,:id_userlogin,:slip_no)");
		$insertTransactionLog->execute([
			':ref_no' => $ref_no,
			':from_account' => $contract_no,
			':destination' => $deptaccount_no,
			':amount' => $dataComing["amt_transfer"],
			':penalty_amt' => $dataComing["penalty_amt"],
			':amount_receive' => $dataComing["amt_transfer"],
			':operate_date' => $dateOper,
			':member_no' => $payload["member_no"],
			':slip_no' => $respWS->payoutslip_no,
			':id_userlogin' => $payload["id_userlogin"]
		]);
		$arrToken = $func->getFCMToken('person',$payload["member_no"]);
		$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
		$$contract_no_format = $contract_no;
		$dataMerge = array();
		$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($deptaccount_no,$func->getConstant('hidden_dep'));
		$dataMerge["CONTRACT_NO"] = $contract_no_format;
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
						':ref_slipno' => $slip_no,
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
						':ref_slipno' => $slip_no,
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