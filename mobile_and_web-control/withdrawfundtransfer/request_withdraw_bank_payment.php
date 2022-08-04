<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','sigma_key','coop_account_no','penalty_amt','fee_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT gba.citizen_id,gba.bank_code,gba.deptaccount_no_bank,csb.itemtype_wtd,
												csb.itemtype_dep,csb.link_withdraw_coopdirect,csb.bank_short_ename,gba.account_payfee
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$flag_transaction_coop = false;
		$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
		$time = time();
		$arrSendData = array();
		$dateOperC = date('c');
		$ref_no = $time.$lib->randomText('all',3);
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$fee_amt = $dataComing["fee_amt"];
		$amt_transfer = $dataComing["amt_transfer"];
		$arrVerifyToken['exp'] = $time + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['coop_account_no'] = $coop_account_no;
		$arrVerifyToken['operate_date'] = $dateOperC;
		$arrVerifyToken['ref_trans'] = $ref_no;
		$arrVerifyToken['destination'] = $rowDataWithdraw["deptaccount_no_bank"];
		$refbank_no = null;
		$etnrefbank_no = null;
		$vccAccID = null;
		
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		if($rowDataWithdraw["bank_code"] == '999'){
			$arrSendData["client_timestamp"] = $dataComing["CLIENT_TIMESTAMP"];
			$arrSendData["client_trans_no"] = $dataComing["CLIENT_TRANS_NO"];
		}
		$conoracle->beginTransaction();
		$wtdResult = $cal_dep->WithdrawMoneyInside($conoracle,$coop_account_no,$rowDataWithdraw["itemtype_wtd"],$amt_transfer,null,$ref_no);
		if($wtdResult["RESULT"]){
			if($coop_account_no == $rowDataWithdraw["account_payfee"]){
				$wtdResultFee = $cal_dep->WithdrawMoneyInside($conoracle,$rowDataWithdraw["account_payfee"],"W16",$fee_amt,$wtdResult,$ref_no);
			}else{
				$wtdResultFee = $cal_dep->WithdrawMoneyInside($conoracle,$rowDataWithdraw["account_payfee"],"W16",$fee_amt,null,$ref_no);
			}
			if($wtdResultFee["RESULT"]){
				$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_withdraw_coopdirect"],$arrSendData);
				if(!$responseAPI["RESULT"]){
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0030";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':fee_amt' => $dataComing["fee_amt"],
						':deptaccount_no' => $coop_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
					];
					$log->writeLog('withdrawtrans',$arrayStruc);
					$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"];
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$conoracle->commit();
					$getLastBookID = $conoracle->prepare("SELECT MAX(BOOK_ID) MAX_BOOK FROM BK_T_NOBOOK WHERE account_no = :account_no");
					$getLastBookID->execute([
						':account_no' => $coop_account_no
					]);
					$rowBookID = $getLastBookID->fetch(PDO::FETCH_ASSOC);
					$insertRemark = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,deptaccount_no,seq_no)
														VALUES(:remark,:deptaccount_no,:seq_no)");
					$insertRemark->execute([
						':remark' => $dataComing["remark"],
						':deptaccount_no' => $coop_account_no,
						':seq_no' => $rowBookID["MAX_BOOK"]
					]);
					$arrExecute = [
						':ref_no' => $ref_no,
						':itemtype' => $rowDataWithdraw["itemtype_wtd"],
						':from_account' => $coop_account_no,
						':destination' => $rowDataWithdraw["deptaccount_no_bank"],
						':amount' => $dataComing["amt_transfer"],
						':fee_amt' => $dataComing["fee_amt"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':amount_receive' => $amt_transfer,
						':oper_date' => $dateOper,
						':member_no' => $payload["member_no"],
						':ref_no1' => $coop_account_no,
						':slip_no' => null,
						':etn_ref' => $etnrefbank_no,
						':id_userlogin' => $payload["id_userlogin"],
						':ref_no_source' => $refbank_no,
						':bank_code' => $rowDataWithdraw["bank_code"] ?? '004'
					];
					$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination,transfer_mode
																,amount,fee_amt,penalty_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																ref_no_1,coop_slip_no,etn_refno,id_userlogin,ref_no_source,bank_code)
																VALUES(:ref_no,:itemtype,:from_account,:destination,'9',:amount,:fee_amt,:penalty_amt,:amount_receive,'-1',:oper_date,'1',:member_no,:ref_no1,
																:slip_no,:etn_ref,:id_userlogin,:ref_no_source,:bank_code)");
					if($insertTransactionLog->execute($arrExecute)){
					}else{
						$message_error = "ไม่สามารถ Insert ลงตาราง gctransaction ได้"."\n"."Query => ".$insertTransactionLog->queryString."\n".json_encode($arrExecute);
						$lib->sendLineNotify($message_error);
					}
					$arrToken = $func->getFCMToken('person',$payload["member_no"]);
					$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
					$dataMerge = array();
					$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($coop_account_no,$func->getConstant('hidden_dep'));
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
							$arrPayloadNotify["TYPE_NOTIFY"] = "2";
							if($lib->sendNotify($arrPayloadNotify,"person")){
								$func->insertHistory($arrPayloadNotify,'2');
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
							$arrPayloadNotify["TYPE_NOTIFY"] = "2";
							if($lib->sendNotifyHW($arrPayloadNotify,"person")){
								$func->insertHistory($arrPayloadNotify,'2');
							}
						}
					}
					$arrayResult['TRANSACTION_NO'] = $ref_no;
					$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0037";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':operate_date' => $dateOper,
						':amt_transfer' => $dataComing["amt_transfer"],
						':penalty_amt' => $dataComing["penalty_amt"],
						':fee_amt' => $dataComing["fee_amt"],
						':deptaccount_no' => $coop_account_no,
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => json_encode($arrResponse)
					];
					$log->writeLog('withdrawtrans',$arrayStruc);
					if(isset($configError[$rowDataWithdraw["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataWithdraw["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conoracle->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0041";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':amt_transfer' => $dataComing["amt_transfer"],
					':penalty_amt' => $dataComing["penalty_amt"],
					':fee_amt' => $dataComing["fee_amt"],
					':deptaccount_no' => $coop_account_no,
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $wtdResultFee["ACTION"]
				];
				$log->writeLog('withdrawtrans',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conoracle->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS0041";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':amt_transfer' => $dataComing["amt_transfer"],
				':penalty_amt' => $dataComing["penalty_amt"],
				':fee_amt' => $dataComing["fee_amt"],
				':deptaccount_no' => $coop_account_no,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $wtdResult["ACTION"]
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
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
