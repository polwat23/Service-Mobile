<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','fee_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$ref_no = date('YmdHis').substr($from_account_no,-3);
		try {
			$argumentWS = [
							"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
							"as_src_deptaccount_no" => $from_account_no,
							"as_dest_deptaccount_no" => $to_account_no,
							"adtm_operate" => date('c'),
							"as_wslipitem_code" => "WTX",
							"as_dslipitem_code" => "DTX",
							"adc_amt" => $dataComing["amt_transfer"],
							"adc_fee" => $dataComing["fee_transfer"],
			];
			$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
			$slip_no = $resultWS->of_withdraw_deposit_transResult;
			$arrayResult['SLIP_NO'] = $slip_no;
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,result_transaction,member_no,
															ref_no_1,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["fee_transfer"],
				':member_no' => $payload["member_no"],
				':ref_no1' => $from_account_no,
				':id_userlogin' => $payload["id_userlogin"],
				':ref_no_source' => $slip_no
			]);
			$arrToken = $func->getFCMToken('person',$payload["member_no"]);
			$templateMessage = $func->getTemplatSystem($dataComing["menu_component"],1);
			$dataMerge = array();
			$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
			$dataMerge["AMT_TRANSFER"] = number_format($dataComing["amt_transfer"],2);
			$dataMerge["DATETIME"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
			$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
			$arrPayloadNotify["TO"] = $arrToken["TOKEN"];
			$arrPayloadNotify["MEMBER_NO"] = $arrToken["MEMBER_NO"];
			$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
			$arrMessage["BODY"] = $message_endpoint["BODY"];
			$arrMessage["PATH_IMAGE"] = null;
			$arrPayloadNotify["PAYLOAD"] = $arrMessage;
			$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
			if($func->insertHistory($arrPayloadNotify,'1')){
				$lib->sendNotify($arrPayloadNotify,"person");
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}catch(SoapFault $e){
			$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,penalty_amt,result_transaction,cancel_date,member_no,ref_no_1,id_userlogin)
															VALUES(:ref_no,'WTX',:from_account,:destination,'1',:amount,:penalty_amt,'-9',NOW(),:member_no,:ref_no1,:id_userlogin)");
			$insertTransactionLog->execute([
				':ref_no' => $ref_no,
				':from_account' => $from_account_no,
				':destination' => $to_account_no,
				':amount' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["fee_transfer"],
				':member_no' => $payload["member_no"],
				':ref_no1' => $from_account_no,
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$arrError = array();
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS8001';
			$lib->addLogtoTxt($arrError,'soap_error');
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>