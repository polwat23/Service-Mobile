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
		try {
			$argumentWS = [
				"as_wspass" => $config["WS_STRC_DB"],
				"as_src_deptaccount_no" => $from_account_no,
				"as_dest_deptaccount_no" => $to_account_no,
				"adtm_operate" => date('c'),
				"as_wslipitem_code" => "WTB",
				"as_dslipitem_code" => "DTB",
				"adc_amt" => $dataComing["amt_transfer"],
				"adc_fee" => $dataComing["penalty_amt"]
			];
			$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
			$slip_no = $resultWS->of_withdraw_deposit_transResult;
			$arrayResult['SLIP_NO'] = $slip_no;
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,amount_receive,trans_flag,result_transaction,member_no,
															coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTB',:from_account,:destination,'1',:amount,:penalty_amt,:amount_receive,'-1','1',:member_no,:slip_no,:id_userlogin,:slip_no)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amount_receive,
				':member_no' => $payload["member_no"],
				':slip_no' => $slip_no,
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
			$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
			foreach($arrToken["LIST_SEND"] as $dest){
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
				if($func->insertHistory($arrPayloadNotify,'2')){
					$lib->sendNotify($arrPayloadNotify,"person");
				}
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}catch(SoapFault $e){
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,amount_receive,result_transaction.trans_flag,cancel_date,member_no,id_userlogin)
															VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,:amount_receive,'-1','-9',NOW(),:member_no,:id_userlogin)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':amount_receive' => $amount_receive,
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$arrayResult["RESPONSE_CODE"] = 'WS8001';
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => date('Y-m-d H:i:s'),
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '2',
					':destination' => $to_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $e->getMessage()
				];
			}else{
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => date('Y-m-d H:i:s'),
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':type_request' => '2',
					':transfer_flag' => '1',
					':destination' => $to_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $e->getMessage()
				];
			}
			$log->writeLog('transferinside',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>