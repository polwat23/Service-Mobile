<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','kbank_ref_no','amt_transfer','citizen_id_enc',
'dept_account_enc','tran_id','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$arrSendData = array();
		$penalty_include = $func->getConstant("include_penalty");
		if($penalty_include == '0'){
			$amt_transfer = $dataComing["amt_transfer"] - $dataComing["penalty_amt"] - $dataComing["fee_amt"];
		}else{
			$amt_transfer = $dataComing["amt_transfer"] - $dataComing["fee_amt"];
		}
		$arrVerifyToken['exp'] = time() + 60;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$arrVerifyToken["tran_id"] = $dataComing["tran_id"];
		$arrVerifyToken["kbank_ref_no"] = $dataComing["kbank_ref_no"];
		$arrVerifyToken['citizen_id_enc'] = $dataComing["citizen_id_enc"];
		$arrVerifyToken['dept_account_enc'] = $dataComing["dept_account_enc"];
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		// Withdraw Inside --------------------------------------
		$fetchDataDeposit = $conmysql->prepare("SELECT bank_code,deptaccount_no_bank FROM gcbindaccount WHERE sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$fetchDepttype->execute([':deptaccount_no' => $coop_account_no]);
		$rowDataDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC);
		$arrayGroup = array();
		$arrayGroup["account_id"] = "11121700";
		$arrayGroup["action_status"] = "1";
		$arrayGroup["atm_no"] = "mobile";
		$arrayGroup["atm_seqno"] = null;
		$arrayGroup["aviable_amt"] = null;
		$arrayGroup["bank_accid"] = $rowDataDeposit["deptaccount_no_bank"];
		$arrayGroup["bank_cd"] = $rowDataDeposit["bank_code"];
		$arrayGroup["branch_cd"] = null;
		$arrayGroup["coop_code"] = $config["COOP_KEY"];
		$arrayGroup["coop_id"] = $config["COOP_ID"];
		$arrayGroup["deptaccount_no"] = $coop_account_no;
		$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
		$arrayGroup["entry_id"] = "admin";
		$arrayGroup["fee_amt"] = $dataComing["penalty_amt"];
		$arrayGroup["feeinclude_status"] = $penalty_include;
		$arrayGroup["item_amt"] = $amt_transfer;
		$arrayGroup["member_no"] = $member_no;
		$arrayGroup["moneytype_code"] = "CBT";
		$arrayGroup["msg_output"] = null;
		$arrayGroup["msg_status"] = null;
		$arrayGroup["operate_date"] = date('c');
		$arrayGroup["oprate_cd"] = "003";
		$arrayGroup["post_status"] = "1";
		$arrayGroup["principal_amt"] = null;
		$arrayGroup["ref_slipno"] = null;
		$arrayGroup["slipitemtype_code"] = "DTX";
		$arrayGroup["stmtitemtype_code"] = "WTX";
		$arrayGroup["system_cd"] = "02";
		$arrayGroup["withdrawable_amt"] = null;
		$ref_slipno = null;
		$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
		try{
			try {
				$argumentWS = [
						"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscuat;Password=iscorfscuat;Unicode=True;coop_id=050001;coop_control=050001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_servResult;
				if($responseSoap->msg_status != '0000'){
					$text = '#Withdraw #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoap->msg_output).' | '.json_encode($responseSoap);
					file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$ref_slipno = $responseSoap->ref_slipno;
				$flag_transaction_coop = true;
			}catch(SoapFault $e){
				$text = '#Withdraw #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($e).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			// -----------------------------------------------
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/withdrawdeposit_kbank',$arrSendData);
			if(!$responseAPI){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,result_transaction,cancel_date,member_no,
															ref_no_1,coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1','-9',NOW(),:member_no
															,:ref_no1,:slip_no,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $dataComing["tran_id"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataDeposit["deptaccount_no_bank"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $amt_transfer,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $dataComing["kbank_ref_no"]
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_servResult;
				$text = '#Withdraw-Cancel Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoapCancel);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer-cancel_error.txt', $text . PHP_EOL, FILE_APPEND);
				$text = '#Withdraw #WS0037 Fund transfer : '.date("Y-m-d H:i:s").' > Timeout | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/fundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0030";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no");
				$fetchSeqno->execute([':deptslip_no' => $ref_slipno]);
				$rowSeqno = $fetchSeqno->fetch();
				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
													VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $coop_account_no,
					':seq_no' => $rowSeqno["SEQ_NO"]
				]);
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,result_transaction,member_no,
															ref_no_1,coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1','1',:member_no,:ref_no1,
															:slip_no,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $dataComing["tran_id"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataDeposit["deptaccount_no_bank"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $amt_transfer,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $dataComing["kbank_ref_no"]
				]);
				$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
				$templateMessage = $func->getTemplatSystem($dataComing["menu_component"],1);
				foreach($arrToken["LIST_SEND"] as $dest){
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
					$dataMerge["AMT_TRANSFER"] = number_format($amt_transfer,2);
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
				$arrayResult['TRANSACTION_NO'] = $dataComing["tran_id"];
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,result_transaction,cancel_date,member_no,
															ref_no_1,coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1','-9',NOW(),:member_no
															,:ref_no1,:slip_no,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $dataComing["tran_id"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataDeposit["deptaccount_no_bank"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $amt_transfer,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $dataComing["kbank_ref_no"]
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_servResult;
				$text = '#Withdraw-Cancel Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoapCancel);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer-cancel_error.txt', $text . PHP_EOL, FILE_APPEND);
				$text = '#Withdraw #WS0037 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/fundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0037";
				$arrayResult['RESPONSE_MESSAGE'] = $arrResponse->RESPONSE_MESSAGE;//$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
			if($flag_transaction_coop){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,amount_receive,trans_flag,result_transaction,cancel_date,member_no,
															ref_no_1,coop_slip_no,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:,'-1','-9',NOW(),:member_no
															,:ref_no1,:slip_no,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $dataComing["tran_id"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataDeposit["deptaccount_no_bank"],
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':amount_receive' => $amt_transfer,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $dataComing["kbank_ref_no"]
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_servResult;
				$text = '#Withdraw-Cancel Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoapCancel);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer-cancel_error.txt', $text . PHP_EOL, FILE_APPEND);
				$text = '#Withdraw #WS0037 Fund transfer : '.date("Y-m-d H:i:s").' > Catch | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/fundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
			}
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS9999';
			$lib->addLogtoTxt($arrError,'exception_error');
			$arrayResult['RESPONSE_CODE'] = "WS9999";
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