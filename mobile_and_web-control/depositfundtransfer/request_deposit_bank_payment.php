<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		try {
			$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
			$time = time();
			$arrSendData = array();
			$arrSendData["remark"] = $dataComing["remark"] ?? null;
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
			$arrayGroup["fee_amt"] = "0";
			$arrayGroup["feeinclude_status"] = "1";
			$arrayGroup["item_amt"] = $dataComing["amt_transfer"];
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
	
			$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
			try {
				$argumentWS = [
						"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoap = $resultWS->of_dept_inf_servResult;
				if($responseSoap->msg_status != '0000'){
					$text = '#Deposit #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoap->msg_output).' | '.json_encode($responseSoap);
					file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0041";
					if($lang_locale == 'th'){
						$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถทำรายการได้ในขณะนี้ กรุณาติดต่อสหกรณ์ #WS0041";
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "Cannot transaction this moment please contact cooperative #WS0041";
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}catch(SoapFault $e){
				$text = '#Deposit #WS0041 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($e).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถทำรายการได้ในขณะนี้ กรุณาติดต่อสหกรณ์ #WS0041";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot transaction this moment please contact cooperative #WS0041";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			// -----------------------------------------------
			$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/deposit/request_deposit_payment',$arrSendData);
			if(!$responseAPI){
				$arrayResult['RESPONSE_CODE'] = "WS0027";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถฝากเงินเข้าบัญชีได้ กรุณาติดต่อสหกรณ์ #WS0027";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot deposit to account please contact cooperative #WS0027";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$transaction_no = $arrResponse->TRANSACTION_NO;
				$ref_no = date('YmdHis').substr($coop_account_no,7);
				$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,destination,amount,result_transaction,member_no,
															ref_no_1,id_userlogin,ref_no_source)
															VALUES(:ref_no,'DTX',:from_account,'1',:destination,:amount,1,:member_no,:ref_no1,:id_userlogin,:ref_no_source)");
				$insertTransactionLog->execute([
					':ref_no' => $ref_no,
					':from_account' => $rowDataDeposit["deptaccount_no_bank"],
					':destination' => $coop_account_no,
					':amount' => $dataComing["amt_transfer"],
					':member_no' => $member_no,
					':ref_no1' => $coop_account_no,
					':id_userlogin' => $payload["id_userlogin"],
					':ref_no_source' => $transaction_no
				]);
				$arrayResult['EXTERNAL_REF'] = $arrResponse->EXTERNAL_REF;
				$arrayResult['TRANSACTION_NO'] = $ref_no;
				$arrayResult['PAYER_ACCOUNT'] = $arrResponse->PAYER_ACCOUNT;
				$arrayResult['PAYER_NAME'] = $arrResponse->PAYER_NAME;
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$arrayGroup["post_status"] = "-1";
				$argumentWS = [
						"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
						"astr_dept_inf_serv" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
				$responseSoapCancel = $resultWS->of_dept_inf_servResult;
				$text = '#Deposit-Cancel Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($responseSoapCancel);
				file_put_contents(__DIR__.'/../../log/soapfundtransfer-cancel_error.txt', $text . PHP_EOL, FILE_APPEND);
				$text = '#Deposit #WS0038 Fund transfer : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrVerifyToken);
				file_put_contents(__DIR__.'/../../log/fundtransfer_error.txt', $text . PHP_EOL, FILE_APPEND);
				$arrayResult['RESPONSE_CODE'] = "WS0038";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถฝากเงินได้ กรุณาติดต่อสหกรณ์ #WS0038";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot deposit please contact cooperative #WS0038";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS9999';
			$lib->addLogtoTxt($arrError,'exception_error');
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "เกิดข้อผิดพลาดบางประการกรุณาติดต่อสหกรณ์ #WS9999";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS9999";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>