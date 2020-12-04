<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$min_amount_deposit = $func->getConstant("min_amount_deposit");
		if($dataComing["amt_transfer"] < (int) $min_amount_deposit){
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($min_amount_deposit,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		try{
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			try {
				$argumentWS = [
								"as_wspass" => $config["WS_STRC_DB"],
								"as_account_no" => $deptaccount_no,
								"as_itemtype_code" => "WTB",
								"adc_amt" => $dataComing["amt_transfer"],
								"adtm_date" => date('c')
				];
				$resultWS = $clientWS->__call("of_chk_withdrawcount_amt", array($argumentWS));
				$amt_transfer = $resultWS->of_chk_withdrawcount_amtResult;
				$getWithdrawal = $conoracle->prepare("SELECT withdrawable_amt FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
				$getWithdrawal->execute([':deptaccount_no' => $deptaccount_no]);
				$rowWithdrawal = $getWithdrawal->fetch(PDO::FETCH_ASSOC);
				$SumAmt_transfer = $amt_transfer + $dataComing["amt_transfer"];
				if($SumAmt_transfer <= $rowWithdrawal["WITHDRAWABLE_AMT"]){
					if($amt_transfer > 0){
						$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
						$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
						$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
						$arrayResult['CAUTION'] = $arrayCaution;
					}
					$arrayResult['PENALTY_AMT'] = $amt_transfer;
					$arrayResult['PENALTY_AMT_FORMAT'] = number_format($amt_transfer,2);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult["RESPONSE_CODE"] = 'WS0067';
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}catch(SoapFault $e){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0042",
					":error_desc" => "ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0042';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}catch(Throwable $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0042",
				":error_desc" => "ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".$e->getMessage(),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service เงินฝากได้ "."\n"."Error => ".$e->getMessage()."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult["RESPONSE_CODE"] = 'WS0042';
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