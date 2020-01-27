<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no'],$dataComing)){
	if(isset($new_token)){
		$arrayResult['NEW_TOKEN'] = $new_token;
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		try {
			$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
			$time = time();
			$arrSendData = array();
			$arrVerifyToken['exp'] = time() + 60;
			$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
			$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
			$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
			$arrVerifyToken['coop_account_no'] = $coop_account_no;
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			// Deposit Inside --------------------------------------
			$fetchDataDeposit = $conmysql->prepare("SELECT bank_code,deptaccount_no_bank FROM gcbindaccount WHERE sigma_key = :sigma_key");
			$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
			$rowDataDeposit = $fetchDataDeposit->fetch();
			$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
			$fetchDepttype->execute([':deptaccount_no' => $coop_account_no]);
			$rowDataDepttype = $fetchDepttype->fetch();
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
			$arrayGroup["entry_id"] = "KBANK";
			$arrayGroup["fee_amt"] = "0";
			$arrayGroup["feeinclude_status"] = "1";
			$arrayGroup["item_amt"] = $dataComing["amt_transfer"];
			$arrayGroup["member_no"] = $payload["member_no"];
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = date('c');
			$arrayGroup["oprate_cd"] = "003";
			$arrayGroup["post_status"] = "1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = "DTB";
			$arrayGroup["stmtitemtype_code"] = "WTB";
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			$ref_slipno = null;
			$clientWS = new SoapClient("http://localhost:81/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
			try {
				$argumentWS = [
						"as_wspass" => "Data Source=127.0.0.1/gcoop;Persist Security Info=True;User ID=iscocen;Password=iscocen;Unicode=True;coop_id=001001;coop_control=001001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_serv_cenResult;
				if($responseSoap->msg_status != '0000'){
					$text = '#Deposit #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoap->msg_output).' | '.json_encode($responseSoap);
					file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$ref_slipno = $responseSoap->ref_slipno;
			}catch(SoapFault $e){
				$text = '#Deposit #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($e).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			// -----------------------------------------------
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/depositfundtransfer_kbank',$arrSendData);
			if(!$responseAPI){
				$arrayResult['RESPONSE_CODE'] = "WS0027";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			$ref_no = date('YmdHis').substr($coop_account_no,7);
			if($arrResponse->RESULT){
				$transaction_no = $arrResponse->TRANSACTION_NO;
				$etn_ref = $arrResponse->EXTERNAL_REF;
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,transfer_mode
															,amount,result_transaction,member_no,
															ref_no_1,etn_refno,id_userlogin,ref_no_source)
															VALUES(:ref_no,'DTX',:from_account,'1',:destination,'9',:amount,'1',:member_no,:ref_no1,:etn_ref,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':etn_ref' => $etn_ref,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $transaction_no
				]);
				$arrayResult['EXTERNAL_REF'] = $etn_ref;
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
				$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,result_transaction,cancel_date,member_no,ref_no_1,id_userlogin)
															VALUES(:ref_no,'DTX',:from_account,:destination,'9',:amount,'-9',NOW(),:member_no,:ref_no1,:id_userlogin)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':id_userlogin' => $payload["id_userlogin"]
				]);
				$arrayGroup["post_status"] = "-1";
				$arrayGroup["atm_no"] = $ref_slipno;
				$argumentWS = [
						"as_wspass" => "Data Source=127.0.0.1/gcoop;Persist Security Info=True;User ID=iscocen;Password=iscocen;Unicode=True;coop_id=001001;coop_control=001001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
				$text = '#Deposit-Cancel Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoapCancel);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer-cancel.txt', $text . PHP_EOL, FILE_APPEND);
				$text = '#Deposit #WS0038 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/fundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0038";
				$arrayResult['RESPONSE_MESSAGE'] = $arrResponse->RESPONSE_MESSAGE;//$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
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