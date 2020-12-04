<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','request_amt','loantype_code','period_payment'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		try {
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$structureReqLoan = array();
			$structureReqLoan["coop_id"] = $config["COOP_ID"];
			$structureReqLoan["member_no"] = $member_no;
			$structureReqLoan["loantype_code"] = $dataComing["loantype_code"];
			$structureReqLoan["operate_date"] = date("c");
			try {
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"atr_lnatm" => $structureReqLoan
				];
				$resultWS = $clientWS->__call("of_initloanrequest_mobile_atm", array($argumentWS));
				$responseSoap = $resultWS->atr_lnatm;
				if($responseSoap->msg_status == '000'){
					$diff_old_contract = $responseSoap->prinbal_clr + $responseSoap->intpayment_clr;
					$structureReqLoanPayment = array();
					$structureReqLoanPayment["coop_id"] = $config["COOP_ID"];
					$structureReqLoanPayment["loantype_code"] = $dataComing["loantype_code"];
					$structureReqLoanPayment["colltype_code"] = null;
					$structureReqLoanPayment["member_no"] = $member_no;
					$structureReqLoanPayment["operate_date"] = date("c");
					$structureReqLoanPayment["contcredit_flag"] = '1';
					$structureReqLoanPayment["loancontract_no"] = 'AUTO';
					$structureReqLoanPayment["entry_id"] = 'mobile';
					$structureReqLoanPayment["loanpermiss_amt"] = $responseSoap->loanpermiss_amt;
					$structureReqLoanPayment["period_payamt"] = $dataComing["period"];
					$structureReqLoanPayment["loanrequest_amt"] = $dataComing["request_amt"];
					$structureReqLoanPayment["account_id"] = $responseSoap->account_id;
					$structureReqLoanPayment["approve_amt"] = $responseSoap->approve_amt;
					$structureReqLoanPayment["fee_amt"] = $responseSoap->fee_amt;
					$structureReqLoanPayment["maxreceive_amt"] = null;
					$structureReqLoanPayment["contclr_no"] = $responseSoap->contclr_no;
					$structureReqLoanPayment["prinbal_clr"] = $responseSoap->prinbal_clr;
					$structureReqLoanPayment["intpayment_clr"] = $responseSoap->intpayment_clr;
					$structureReqLoanPayment["item_amt"] = $responseSoap->prinbal_clr + $responseSoap->intpayment_clr;
					try {
						$argumentWS = [
							"as_wspass" => $config["WS_STRC_DB"],
							"atr_lnatm" => $structureReqLoanPayment
						];
						if($dataComing["loantype_code"] == '02023'){
							$receive_net = round($dataComing["request_amt"] - $diff_old_contract,2);
						}else{
							$receive_net = round($responseSoap->loanpermiss_amt - $diff_old_contract,2);
						}
						$resultWS = $clientWS->__call("of_saveloanmobile_atm_ivr", array($argumentWS));
						$responseSoapSave = $resultWS->of_saveloanmobile_atm_ivrResult;
						file_put_contents(__DIR__.'/../../log/requestloanonline.txt', json_encode($responseSoap) . PHP_EOL, FILE_APPEND);
						$insertReqLoan = $conmysql->prepare("INSERT INTO logreqloan(member_no,loantype_code,request_amt,period_payment,period,deptaccount_no,loanpermit_amt,diff_old_contract,receive_net,id_userlogin)
															VALUES(:member_no,:loantype_code,:request_amt,:period_payment,:period,:account_id,:loan_permit,:diff_old_contract,:receive_net,:id_userlogin)");
						$insertReqLoan->execute([
							':member_no' => $payload["member_no"],
							':loantype_code' => $dataComing["loantype_code"],
							':request_amt' => $dataComing["request_amt"],
							':period_payment' => $dataComing["period_payment"],
							':period' => $dataComing["period"],
							':account_id' => $responseSoap->account_id,
							':loan_permit' => $responseSoap->loanpermiss_amt,
							':diff_old_contract' => $diff_old_contract,
							':receive_net' => $receive_net,
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$getLoanDesc = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
						$getLoanDesc->execute([':loantype_code' => $dataComing["loantype_code"]]);
						$rowLoanDesc = $getLoanDesc->fetch(PDO::FETCH_ASSOC);
						$arrToken = $func->getFCMToken('person',array($payload["member_no"]));
						$templateMessage = $func->getTemplateSystem($dataComing["menu_component"]);
						$dataMerge = array();
						$dataMerge["LOANTYPE_DESC"] = $rowLoanDesc["LOANTYPE_DESC"];
						$dataMerge["REQUEST_AMT"] = number_format($dataComing["request_amt"],2);
						$dataMerge["PERIOD"] = $dataComing["period"];
						$dataMerge["PERIOD_PAYMENT"] = number_format($dataComing["period_payment"],2);
						$dataMerge["CLRAMT"] = number_format($diff_old_contract,2);
						$dataMerge["RECEIVENET_AMT"] = number_format($dataComing["request_amt"] - $diff_old_contract,2);
						$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
						foreach($arrToken["LIST_SEND"] as $dest){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
							$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
							$arrMessage["BODY"] = $message_endpoint["BODY"];
							$arrMessage["PATH_IMAGE"] = null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
							if($lib->sendNotify($arrPayloadNotify,"person")){
								$func->insertHistory($arrPayloadNotify,'2');
							}
						}
						$arrayResult['TRANSACTION_DATE'] = $lib->convertdate(date('Y-m-d'),'d m Y');
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}catch(SoapFault $e){
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS0063",
							":error_desc" => "ไม่สามารถคำนวณสิทธิ์กู้ได้ "."\n"."Error => ".($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$insertReqLoan = $conmysql->prepare("INSERT INTO logreqloan(member_no,loantype_code,request_amt,period_payment,period,deptaccount_no,loanpermit_amt,diff_old_contract,receive_net,id_userlogin)
															VALUES(:member_no,:loantype_code,:request_amt,:period_payment,:period,:account_id,:loan_permit,:diff_old_contract,:receive_net,:id_userlogin)");
						$insertReqLoan->execute([
							':member_no' => $payload["member_no"],
							':loantype_code' => $dataComing["loantype_code"],
							':request_amt' => $dataComing["request_amt"],
							':period_payment' => $dataComing["period_payment"],
							':period' => $dataComing["period"],
							':account_id' => $responseSoap->account_id,
							':loan_permit' => $responseSoap->loanpermiss_amt,
							':diff_old_contract' => $diff_old_contract,
							':receive_net' => round($responseSoap->loanpermiss_amt - $diff_old_contract,2),
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$arrayResult['RESPONSE_CODE'] = "WS0063";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS0058",
						":error_desc" => "ไม่สามารถคำนวณชำระต่องวดได้ "."\n"."Error => ".$responseSoap->msg_output,
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult['RESPONSE_CODE'] = "WS0058";
					if(isset($responseSoap->msg_output) && $responseSoap->msg_output != ""){
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];//$responseSoap->msg_output;
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0058",
					":error_desc" => "คำนวณสิทธิ์กู้ไม่ได้ "."\n"."Error => ".($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0058";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0063",
				":error_desc" => "ต่อ Service ไปเงินกู้ไมได้ "."\n".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ต่อ Service ไปเงินกู้ไมได้ "."\n"."DATA => ".json_encode($dataComing)."\n"."Error => ".$e->getMessage();
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS0062";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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