<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$to_deptaccount_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$from_deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$checkWithdraw = $cal_dep->depositCheckWithdrawRights($from_deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
		if($checkWithdraw["RESULT"]){
			$checkDeposit = $cal_dep->depositCheckDepositRights($to_deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
			if($checkDeposit["RESULT"]){
				try {
					$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
					try{
						$argumentWS = [
							"as_wspass" => $config["WS_PASS"],
							"as_account_no" => $dataComing["from_deptaccount_no"],
							"as_itemtype_code" => "WES",
							"adc_amt" => $dataComing["amt_transfer"],
							"adtm_date" => date('c')
						];
						$resultWS = $clientWS->__call("of_chk_withdrawcount_amt", array($argumentWS));
						$feeAmt = $resultWS->of_chk_withdrawcount_amtResult;
						if($feeAmt > 0){
							$arrayResult['PENALTY_AMT'] = $feeAmt;
							$arrayResult['PENALTY_AMT_FORMAT'] = $feeAmt;
							$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
							$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
							$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
							$arrayResult['CAUTION'] = $arrayCaution;
						}
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
				$arrayResult['RESPONSE_CODE'] = $checkDeposit["RESPONSE_CODE"];
				if($checkDeposit["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($checkDeposit["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			
		}else{
			$arrayResult['RESPONSE_CODE'] = $checkWithdraw["RESPONSE_CODE"];
			if($checkWithdraw["RESPONSE_CODE"] == 'WS0056'){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($checkWithdraw["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
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