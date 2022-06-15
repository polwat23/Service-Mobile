<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$amt_transfer = $dataComing["amt_transfer"] - $dataComing["fee_amt"];
		$arrSendData = array();
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		// Deposit Inside --------------------------------------
		$fetchDataDeposit = $conmysql->prepare("SELECT bank_code,deptaccount_no_bank FROM gcbindaccount WHERE sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$fetchDepttype->execute([':deptaccount_no' => $coop_account_no]);
		$rowDataDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC);
		$arrayGroup = array();
		$arrayGroup["account_id"] = $func->getConstant("operative_account");
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
		$arrayGroup["entry_id"] = "KBANK";
		$arrayGroup["fee_amt"] = "0";
		$arrayGroup["feeinclude_status"] = "1";
		$arrayGroup["item_amt"] = $amt_transfer;
		$arrayGroup["member_no"] = $member_no;
		$arrayGroup["moneytype_code"] = "CBT";
		$arrayGroup["msg_output"] = null;
		$arrayGroup["msg_status"] = null;
		$arrayGroup["operate_date"] = $dateOperC;
		$arrayGroup["oprate_cd"] = "002";
		$arrayGroup["post_status"] = "1";
		$arrayGroup["principal_amt"] = null;
		$arrayGroup["ref_slipno"] = null;
		$arrayGroup["slipitemtype_code"] = "DTB";
		$arrayGroup["stmtitemtype_code"] = "WTB";
		$arrayGroup["system_cd"] = "02";
		$arrayGroup["withdrawable_amt"] = null;
		$ref_slipno = null;
		$ref_no = time().$lib->randomText('all',3);
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_serv_cenResult;
				if($responseSoap->msg_status != '0000'){
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':sigma_key' => $dataComing["sigma_key"],
						':amt_transfer' => $amt_transfer,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $responseSoap->msg_output
					];
					$log->writeLog('deposittrans',$arrayStruc);
					if($responseSoap->msg_status == '0098'){
						$arrayResult['RESPONSE_MESSAGE'] = str_replace($responseSoap->msg_status,'',str_ireplace(["\r","\n",'\r','\n'],'',$responseSoap->msg_output))." บาท";
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				
				$ref_slipno = $responseSoap->ref_slipno;
				$updateSyncNoti = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptslip_no = :ref_slipno");
				$updateSyncNoti->execute([':ref_slipno' => $ref_slipno]);
				$flag_transaction_coop = true;
			}catch(SoapFault $e){
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $e->getMessage()
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			// -----------------------------------------------
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/depositfundtransfer_kbank',$arrSendData);
			if(!$responseAPI["RESULT"]){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,coop_slip_no,id_userlogin,bank_code)
															VALUES(:ref_no,'DTB',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'-9',
															NOW(),:member_no,:ref_no1,:slip_no,:id_userlogin,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
				$arrayResult['RESPONSE_CODE'] = "WS0027";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$fetchSeqno = $conoracle->prepare("SELECT SEQ_NO FROM dpdeptstatement WHERE deptslip_no = :deptslip_no");
				$fetchSeqno->execute([':deptslip_no' => $ref_slipno]);
				$rowSeqno = $fetchSeqno->fetch(PDO::FETCH_ASSOC);
				$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
													VALUES(:remark,:deptaccount_no,:seq_no)");
				$insertRemark->execute([
					':remark' => $dataComing["remark"],
					':deptaccount_no' => $coop_account_no,
					':seq_no' => $rowSeqno["SEQ_NO"]
				]);
				$transaction_no = $arrResponse->TRANSACTION_NO;
				$etn_ref = $arrResponse->EXTERNAL_REF;
				$arrExecute = [
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':etn_ref' => $etn_ref,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $transaction_no,
					':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
				];
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
															ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
															VALUES(:ref_no,'DTB',:from_account,'1',:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'1',:member_no,
															:ref_no1,:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
				if($insertTransactionLog->execute($arrExecute)){
				}else{
					$arrLogTemp = array();
					$arrLogTemp["DATA"] = $arrExecute;
					$arrLogTemp["QUERY"] = $insertTransactionLog;
					$lib->addLogtoTxt($arrLogTemp,'log_deposit_transaction_temp');
				}
				$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
				$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
				foreach($arrToken["LIST_SEND"] as $dest){
					if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
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
						$arrPayloadNotify["SEND_BY"] = "system";
						if($func->insertHistory($arrPayloadNotify,'2')){
							$lib->sendNotify($arrPayloadNotify,"person");
						}
					}
				}
				$arrayResult['EXTERNAL_REF'] = $etn_ref;
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
				$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
				$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,coop_slip_no,id_userlogin,bank_code)
															VALUES(:ref_no,'DTB',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:id_userlogin,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
				$arrayResult['RESPONSE_CODE'] = "WS0038";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':sigma_key' => $dataComing["sigma_key"],
					':amt_transfer' => $amt_transfer,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $arrResponse->RESPONSE_MESSAGE
				];
				$log->writeLog('deposittrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
			if($flag_transaction_coop){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,cancel_date,member_no,ref_no_1,coop_slip_no,id_userlogin,bank_code)
															VALUES(:ref_no,'DTB',:from_account,:destination,'9',:amount,:fee_amt,:amount_receive,'1',:operate_date,'-9',NOW(),:member_no,:ref_no1,:slip_no,:id_userlogin,:bank_code)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':fee_amt' => $dataComing["fee_amt"],
					':amount_receive' => $amt_transfer,
					':operate_date' => $dateOper,	
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':slip_no' => $ref_slipno,
					':id_userlogin' => $payload["id_userlogin"],
					':bank_code' => $rowDataDeposit["bank_code"] ?? '004'
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
			}
			$arrayResult["RESPONSE_CODE"] = 'WS9999';
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':sigma_key' => $dataComing["sigma_key"],
				':amt_transfer' => $amt_transfer,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $e->getMessage()
			];
			$log->writeLog('deposittrans',$arrayStruc);
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