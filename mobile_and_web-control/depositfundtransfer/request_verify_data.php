<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$getBankDisplay = $conmysql->prepare("SELECT cs.link_inquirydep_coopdirect,cs.bank_short_ename,gc.bank_code,cs.fee_deposit,gc.bank_account_name
												FROM gcbindaccount gc LEFT JOIN csbankdisplay cs ON gc.bank_code = cs.bank_code
												WHERE gc.sigma_key = :sigma_key and gc.bindaccount_status = '1'");
		$getBankDisplay->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $getBankDisplay->fetch(PDO::FETCH_ASSOC);
		$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"],$rowDataDeposit["bank_code"]);
		if($arrRightDep["RESULT"]){
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["MemberID"] = substr($member_no,-6);
			$arrDataAPI["ToCoopAccountNo"] = $dataComing["deptaccount_no"];
			$arrDataAPI["FromBankCode"] = $rowDataDeposit["bank_code"];
			$arrDataAPI["FromBankAccountNo"] = $dataComing["bank_account_no"];
			$arrDataAPI["TransferFee"] = $rowDataDeposit["fee_deposit"];
			$arrDataAPI["DepositAmount"] = $dataComing["amt_transfer"];
			$arrDataAPI["UserRequestDate"] = date('c');
			$arrDataAPI["Note"] = "Check Fee of VerifyData in Deposit";
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/CheckDepositFee",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS9999",
					":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckDepositFee",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckDepositFee";
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponseAPI = json_decode($arrResponseAPI);
			if($arrResponseAPI->responseCode == "200"){
				if($rowDataDeposit["bank_code"] == '025'){
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
					$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataDeposit["link_inquirydep_coopdirect"],$arrSendData);
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
						$func->MaintenanceMenu($dataComing["menu_component"]);
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
					$arrResponse = json_decode($responseAPI);
					if($arrResponse->RESULT){
						$arrayResult['FEE_AMT'] = $rowDataDeposit["fee_deposit"];
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
						if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else if($rowDataDeposit["bank_code"] == '004'){
					$arrayResult['BANK_ACCOUNT_ENC'] = $dataComing["bank_account_no"];
					$arrayResult['ACCOUNT_NAME'] = $arrResponseAPI->toCOOPAccountName;
					$arrayResult['RESULT'] = TRUE;
				}else if($rowDataDeposit["bank_code"] == '006'){
					if($rowDataDeposit["fee_deposit"] > 0){
						$arrayResult['FEE_AMT'] = $rowDataDeposit["fee_deposit"];
						$arrayResult['FEE_AMT_FORMAT'] = number_format($arrayResult["FEE_AMT"],2);
					}
					$arrayResult['BANK_ACCOUNT_ENC'] = $dataComing["bank_account_no"];
					$arrayResult['ACCOUNT_NAME'] = $arrResponseAPI->toCOOPAccountName;
					$arrayResult['RESULT'] = TRUE;
				}else if($rowDataDeposit["bank_code"] == '014'){
					$arrayResult['FEE_AMT'] = $rowDataDeposit["fee_deposit"];
					$arrayResult['FEE_AMT_FORMAT'] = number_format($arrayResult["FEE_AMT"],2);
					$arrayResult['BANK_ACCOUNT_ENC'] = $dataComing["bank_account_no"];
					$arrayResult['ACCOUNT_NAME'] = $rowDataDeposit["bank_account_name"];
					$arrayResult['RESULT'] = TRUE;
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0028";
				if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
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