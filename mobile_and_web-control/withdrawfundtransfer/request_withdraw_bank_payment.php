<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','kbank_ref_no','amt_transfer','citizen_id_enc',
'dept_account_enc','tran_id','sigma_key','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		try{
			$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
			$time = time();
			$arrSendData = array();
			$penalty_include = $func->getConstant("include_penalty");
			if($penalty_include == '0'){
				$amt_transfer = $dataComing["amt_transfer"] - $dataComing["penelty_amt"] - $dataComing["fee_amt"];
			}else{
				$amt_transfer = $dataComing["amt_transfer"];
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
			$rowDataDeposit = $fetchDataDeposit->fetch();
			$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
			$fetchDepttype->execute([':deptaccount_no' => $coop_account_no]);
			$rowDataDepttype = $fetchDepttype->fetch();
			$arrayGroup = array();
			$arrayGroup["account_id"] = "11121700";
			$arrayGroup["action_status"] = "1";
			$arrayGroup["atm_no"] = $coop_account_no;
			$arrayGroup["atm_seqno"] = $time;
			$arrayGroup["aviable_amt"] = null;
			$arrayGroup["bank_accid"] = $rowDataDeposit["deptaccount_no_bank"];
			$arrayGroup["bank_cd"] = $rowDataDeposit["bank_code"];
			$arrayGroup["branch_cd"] = null;
			$arrayGroup["coop_code"] = $config["COOP_KEY"];
			$arrayGroup["coop_id"] = $config["COOP_ID"];
			$arrayGroup["deptaccount_no"] = $coop_account_no;
			$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
			$arrayGroup["entry_id"] = "admin";
			$arrayGroup["fee_amt"] = $dataComing["fee_amt"];
			$arrayGroup["feeinclude_status"] = $penalty_include;
			$arrayGroup["item_amt"] = $amt_transfer;
			$arrayGroup["member_no"] = $payload["member_no"];
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = date('c');
			$arrayGroup["oprate_cd"] = "002";
			$arrayGroup["post_status"] = "1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = "WTX";
			$arrayGroup["stmtitemtype_code"] = "DTX";
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			
			$clientWS = new SoapClient("http://localhost:81/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
			try {
				$argumentWS = [
						"as_wspass" => "Data Source=127.0.0.1/gcoop;Persist Security Info=True;User ID=iscocen;Password=iscocen;Unicode=True;coop_id=001001;coop_control=001001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_serv_cenResult;
				if($responseSoap->msg_status != '0000'){
					$text = '#Withdraw #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoap->msg_output).' | '.json_encode($responseSoap);
					file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
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
				$arrayResult['RESPONSE_CODE'] = "WS0030";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,result_transaction,member_no,
															ref_no_1,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,'1',:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $dataComing["tran_id"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataDeposit["deptaccount_no_bank"],
					':amount' => $amt_transfer,
					':fee_amt' => $dataComing["fee_amt"] ?? 0,
					':penalty_amt' => $dataComing["penelty_amt"] ?? 0,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $dataComing["kbank_ref_no"]
				]);
				$arrayResult['TRANSACTION_NO'] = $dataComing["tran_id"];
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
															,amount,fee_amt,penalty_amt,result_transaction,cancel_date,member_no,
															ref_no_1,id_userlogin,ref_no_source)
															VALUES(:ref_no,'WTX',:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,'-9',NOW(),:member_no
															,:ref_no1,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $dataComing["tran_id"],
					':from_account' => $coop_account_no,
					':destination' => $rowDataDeposit["deptaccount_no_bank"],
					':amount' => $amt_transfer,
					':fee_amt' => $dataComing["fee_amt"] ?? 0,
					':penalty_amt' => $dataComing["penelty_amt"] ?? 0,
					':member_no' => $payload["member_no"],
					':ref_no1' => $coop_account_no,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $dataComing["kbank_ref_no"]
				]);
				$arrayGroup["post_status"] = "-1";
				$argumentWS = [
						"as_wspass" => "Data Source=127.0.0.1/gcoop;Persist Security Info=True;User ID=iscocen;Password=iscocen;Unicode=True;coop_id=001001;coop_control=001001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_serv_cenResult;
				$text = '#Withdraw-Cancel Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoapCancel);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer-cancel_error.txt', $text . PHP_EOL, FILE_APPEND);
				$text = '#Withdraw #WS0037 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/fundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0037";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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