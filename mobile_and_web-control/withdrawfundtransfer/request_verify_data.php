<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataDeposit = $conmysql->prepare("SELECT csb.itemtype_wtd,csb.link_inquirywithd_coopdirect,gba.bank_code,csb.bank_short_ename,csb.fee_withdraw
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataWithdraw = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$fee_amt = $rowDataWithdraw["fee_withdraw"];
		$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],$rowDataWithdraw["itemtype_wtd"],$fee_amt);
		if($arrInitDep["RESULT"]){
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"],$rowDataWithdraw["bank_code"]);
			if($arrRightDep["RESULT"]){
				try {
					$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
					try{
						$argumentWS = [
							"as_wspass" => $config["WS_PASS"],
							"as_account_no" => $deptaccount_no,
							"as_itemtype_code" => $rowDataWithdraw["itemtype_wtd"],
							"adc_amt" => $dataComing["amt_transfer"],
							"adtm_date" => date('c')
						];
						$resultWS = $clientWS->__call("of_chk_withdrawcount_amt", array($argumentWS));
						$feeAmt = $resultWS->of_chk_withdrawcount_amtResult;
						$constantDep = $cal_dep->getConstantAcc($deptaccount_no);
						if($arrInitDep["SUM_REMAIN"] - $feeAmt < $constantDep["MINPRNCBAL"]){
							$arrayResult['RESPONSE_CODE'] = "WS0100";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
						$dateOperC = date('c');
						$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
						$amt_transfer = $dataComing["amt_transfer"];
						$getDataUser = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE deptaccount_no_bank = :bank_account_no 
															and member_no = :member_no and bindaccount_status = '1'");
						$getDataUser->execute([
							':bank_account_no' => $dataComing["bank_account_no"],
							':member_no' => $payload["member_no"]
						]);
						$rowDataUser = $getDataUser->fetch(PDO::FETCH_ASSOC);
						$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
																FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
																WHERE MB.member_no = :member_no");
						$fetchMemberName->execute([
							':member_no' => $member_no
						]);
						$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
						if($rowDataWithdraw["bank_code"] == '006'){
							$arrSendData = array();
							$arrVerifyToken = array();
							$arrVerifyToken['exp'] = time() + 300;
							$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
							$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
							$arrVerifyToken['bank_code'] = $rowDataWithdraw["bank_code"];
							$arrVerifyToken['tran_date'] = $dateOper;
							$arrVerifyToken['amt_transfer'] = $amt_transfer;
							$arrVerifyToken['bank_account'] = $dataComing["bank_account_no"];
							$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
							$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
							$arrSendData["verify_token"] = $verify_token;
							$arrSendData["app_id"] = $config["APP_ID"];
							$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_inquirywithd_coopdirect"],$arrSendData);
							if(!$responseAPI["RESULT"]){
								$filename = basename(__FILE__, '.php');
								$arrayResult['RESPONSE_CODE'] = "WS0027";
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':deptaccount_no' => $deptaccount_no,
									':amt_transfer' => $amt_transfer,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
								];
								$log->writeLog('withdrawtrans',$arrayStruc);
								$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
								$lib->sendLineNotify($message_error);
								$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
								$func->MaintenanceMenu($dataComing["menu_component"]);
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
							$arrResponse = json_decode($responseAPI);
							if($arrResponse->RESULT){
								if($fee_amt > 0){
									$arrayResult['FEE_AMT'] = $fee_amt;
									$arrayResult['FEE_AMT_FORMAT'] = number_format($fee_amt,2);
								}
							}else{
								$arrayResult['RESPONSE_CODE'] = "WS0038";
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':deptaccount_no' => $deptaccount_no,
									':amt_transfer' => $amt_transfer,
									':response_code' => $arrResponse->RESPONSE_CODE,
									':response_message' => $arrResponse->RESPONSE_MESSAGE
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
						}else if($rowDataWithdraw["bank_code"] == '025'){
							$arrayResult['SOURCE_REFNO'] = $arrResponse->SOURCE_REFNO;
							$arrayResult['ETN_REFNO'] = $arrResponse->ETN_REFNO;
						}else if($rowDataWithdraw["bank_code"] == '004'){
							$time = date("Hi");
							if($time >= '0000' && $time <= '0200'){
								$arrayResult['RESPONSE_CODE'] = "WS0035";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
							$arrSendData = array();
							$arrVerifyToken['exp'] = time() + 300;
							$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
							$arrVerifyToken["operate_date"] = $dateOperC;
							$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
							$arrVerifyToken['deptaccount_no'] = $deptaccount_no;
							$arrVerifyToken['bank_account_no'] = $dataComing["bank_account_no"];
							$verify_token = $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
							$arrSendData["verify_token"] = $verify_token;
							$arrSendData["app_id"] = $config["APP_ID"];
							$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_inquirywithd_coopdirect"],$arrSendData);
							if(!$responseAPI["RESULT"]){
								$arrayResult['RESPONSE_CODE'] = "WS0028";
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':amt_transfer' => $dataComing["amt_transfer"],
									':deptaccount_no' => $deptaccount_no,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
								];
								$log->writeLog('withdrawtrans',$arrayStruc);
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
							$arrResponse = json_decode($responseAPI);
							if($arrResponse->RESULT){
								$arrayResult['REF_KBANK'] = $arrResponse->REF_KBANK;
								$arrayResult['CITIZEN_ID_ENC'] = $arrResponse->CITIZEN_ID_ENC;
								$arrayResult['BANK_ACCOUNT_ENC'] = $arrResponse->BANK_ACCOUNT_ENC;
								$arrayResult['TRAN_ID'] = $arrResponse->TRAN_ID;
							}else{
								$arrayResult['RESPONSE_CODE'] = "WS0042";
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':amt_transfer' => $amt_transfer,
									':deptaccount_no' => $deptaccount_no,
									':response_code' => $arrResponse->RESPONSE_CODE,
									':response_message' => $arrResponse->RESPONSE_MESSAGE
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
						}else if($rowDataWithdraw["bank_code"] == '014'){
							$arrSendData = array();
							$arrVerifyToken = array();
							$arrVerifyToken['exp'] = time() + 300;
							$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
							$arrVerifyToken['member_no'] = $payload["member_no"];
							$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
							$arrVerifyToken['amt_transfer'] = $amt_transfer;
							$arrVerifyToken['fee_amt'] = $fee_amt;
							$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
							$arrSendData["verify_token"] = $verify_token;
							$arrSendData["app_id"] = $config["APP_ID"];
							$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataWithdraw["link_inquirywithd_coopdirect"],$arrSendData);
							if(!$responseAPI["RESULT"]){
								$filename = basename(__FILE__, '.php');
								$arrayResult['RESPONSE_CODE'] = "WS0027";
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':deptaccount_no' => $deptaccount_no,
									':amt_transfer' => $amt_transfer,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
								];
								$log->writeLog('withdrawtrans',$arrayStruc);
								$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
								$lib->sendLineNotify($message_error);
								$func->MaintenanceMenu($dataComing["menu_component"]);
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
							$arrResponse = json_decode($responseAPI);
							if($arrResponse->RESULT){
								if($fee_amt > 0){
									$arrayResult['FEE_AMT'] = $fee_amt;
									$arrayResult['FEE_AMT_FORMAT'] = number_format($fee_amt,2);
								}
							}else{
								$arrayResult['RESPONSE_CODE'] = "WS0038";
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':deptaccount_no' => $deptaccount_no,
									':amt_transfer' => $amt_transfer,
									':response_code' => $arrResponse->RESPONSE_CODE,
									':response_message' => $arrResponse->RESPONSE_MESSAGE
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
						}
						$arrayResult['ACCOUNT_NAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
						if(isset($feeAmt) && $feeAmt > 0){
							$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
							$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
							$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
							$arrayResult['CAUTION'] = $arrayCaution;
							$arrayResult['PENALTY_AMT'] = $feeAmt;
							$arrayResult['PENALTY_AMT_FORMAT'] = number_format($feeAmt,2);
						}
						$arrayResult['TRAN_TIME'] = $arrResponse->TRAN_TIME;
						$arrayResult['TOKEN_ID'] = $arrResponse->TOKEN_ID;
						$arrayResult['TRAN_UNIQ'] = $arrResponse->TRAN_UNIQ;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}catch(SoapFault $e){
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS8001",
							":error_desc" => ($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS8001';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}catch(SoapFault $e){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS9999",
						":error_desc" => "Cannot connect server Deposit API ".$config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl",
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl";
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_CODE'] = "WS9999";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
				if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = $arrInitDep["RESPONSE_CODE"];
			if($arrInitDep["RESPONSE_CODE"] == 'WS0056'){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrInitDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
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