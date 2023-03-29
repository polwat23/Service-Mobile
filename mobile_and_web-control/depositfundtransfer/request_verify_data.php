<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$getBankDisplay = $conmysql->prepare("SELECT cs.link_inquirydep_coopdirect,cs.bank_short_ename,gc.bank_code,cs.fee_deposit,cs.bank_short_ename,cs.itemtype_dep
												FROM gcbindaccount gc LEFT JOIN csbankdisplay cs ON gc.bank_code = cs.bank_code
												WHERE gc.sigma_key = :sigma_key and gc.bindaccount_status = '1'");
		$getBankDisplay->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowBankDisplay = $getBankDisplay->fetch(PDO::FETCH_ASSOC);
		$checkSeqAmt = $cal_dep->getSequestAmount($dataComing["deptaccount_no"],$rowBankDisplay["itemtype_dep"]);
		if($checkSeqAmt["RESULT"]){
			if($checkSeqAmt["CAN_DEPOSIT"]){
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0104";
				$arrayResult['RESPONSE_MESSAGE'] = $checkSeqAmt["SEQUEST_DESC"];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = $checkSeqAmt["RESPONSE_CODE"];
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$constantDep = $cal_dep->getConstantAcc($deptaccount_no);
		$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
		$dateOperC = date('c');
		$arrayGroup = array();
		$arrayGroup["account_id"] = null;
		$arrayGroup["action_status"] = "9";
		$arrayGroup["atm_no"] = "MOBILE";
		$arrayGroup["atm_seqno"] = null;
		$arrayGroup["aviable_amt"] = null;
		$arrayGroup["bank_accid"] = null;
		$arrayGroup["bank_cd"] = '025';
		$arrayGroup["branch_cd"] = null;
		$arrayGroup["coop_code"] = $config["COOP_KEY"];
		$arrayGroup["coop_id"] = "065001";
		$arrayGroup["deptaccount_no"] = $deptaccount_no;
		$arrayGroup["depttype_code"] = $constantDep["DEPTTYPE_CODE"];
		$arrayGroup["entry_id"] = "MOBILE";
		$arrayGroup["fee_amt"] = 0;
		$arrayGroup["fee_operate_cd"] = '0';
		$arrayGroup["feeinclude_status"] = '1';
		$arrayGroup["item_amt"] = $dataComing["amt_transfer"];
		$arrayGroup["member_no"] = $member_no;
		$arrayGroup["moneytype_code"] = "CBT";
		$arrayGroup["msg_output"] = null;
		$arrayGroup["msg_status"] = null;
		$arrayGroup["operate_date"] = $dateOperC;
		$arrayGroup["oprate_cd"] = "003";
		$arrayGroup["post_status"] = "1";
		$arrayGroup["principal_amt"] = null;
		$arrayGroup["ref_app"] = "MOBILE";
		$arrayGroup["ref_slipno"] = null;
		$arrayGroup["slipitemtype_code"] = $rowBankDisplay["itemtype_dep"];
		$arrayGroup["stmtitemtype_code"] = $rowBankDisplay["itemtype_dep"];
		$arrayGroup["system_cd"] = "02";
		$arrayGroup["withdrawable_amt"] = null;
		try {
			$argumentWS = [
				"as_wspass" => $config["WS_PASS"],
				"astr_dept_inf_serv" => $arrayGroup
			];
			$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
			$responseSoap = $resultWS->of_dept_inf_servResult;
			if($responseSoap->msg_status == '0000'){
				$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
															FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
															WHERE MB.member_no = :member_no");
				$fetchMemberName->execute([
					':member_no' => $member_no
				]);
				$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
				$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
				$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"],$rowBankDisplay["bank_code"]);
				if($arrRightDep["RESULT"]){
					if($rowBankDisplay["bank_code"] == '025'){
						$dateOperC = date('c');
						$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
						$arrVerifyToken['exp'] = time() + 300;
						$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
						$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
						$arrVerifyToken['operate_date'] = $dateOperC;
						$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
						$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
						$arrSendData["verify_token"] = $verify_token;
						$arrSendData["app_id"] = $config["APP_ID"];
						$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowBankDisplay["link_inquirydep_coopdirect"],$arrSendData);
						if(!$responseAPI["RESULT"]){
							$filename = basename(__FILE__, '.php');
							$arrayResult['RESPONSE_CODE'] = "WS0027";
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOper,
								':sigma_key' => $dataComing["sigma_key"],
								':amt_transfer' => $dataComing["amt_transfer"],
								':response_code' => $arrayResult['RESPONSE_CODE'],
								':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
							];
							$log->writeLog('deposittrans',$arrayStruc);
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
							$arrayResult['FEE_AMT'] = $arrResponse->FEE_AMT;
							$arrayResult['FEE_AMT_FORMAT'] = number_format($arrayResult["FEE_AMT"],2);
							$arrayResult['SOURCE_REFNO'] = $arrResponse->SOURCE_REFNO;
							$arrayResult['ETN_REFNO'] = $arrResponse->ETN_REFNO;
							$arrayResult['ACCOUNT_NAME'] = $account_name_th;
							$arrayResult['RESULT'] = TRUE;
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0038";
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOper,
								':sigma_key' => $dataComing["sigma_key"],
								':amt_transfer' => $dataComing["amt_transfer"],
								':response_code' => $arrResponse->RESPONSE_CODE,
								':response_message' => $arrResponse->RESPONSE_MESSAGE
							];
							$log->writeLog('deposittrans',$arrayStruc);
							if(isset($configError[$rowBankDisplay["BANK_SHORT_ENAME"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowBankDisplay["BANK_SHORT_ENAME"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
							}else{
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							}
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}else if($rowBankDisplay["BANK_CODE"] == '006'){
						if($rowBankDisplay["FEE_DEPOSIT"] > 0){
							$arrayResult['FEE_AMT'] = $rowBankDisplay["FEE_DEPOSIT"];
							$arrayResult['FEE_AMT_FORMAT'] = number_format($arrayResult["FEE_AMT"],2);
						}
						$arrayResult['ACCOUNT_NAME'] = $account_name_th;
						$arrayResult['RESULT'] = TRUE;
					}else{
						$arrayResult['ACCOUNT_NAME'] = $account_name_th;
						$arrayResult['RESULT'] = TRUE;
					}
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
					if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
						$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0444";
				$arrayResult['RESPONSE_MESSAGE'] = $responseSoap->msg_output;
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS9999",
				":error_desc" => json_encode($e,JSON_UNESCAPED_UNICODE),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl";
			$lib->sendLineNotify($message_error);
			$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_CODE'] = "WS9999";
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
