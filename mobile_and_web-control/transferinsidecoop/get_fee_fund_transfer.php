<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		try{
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl");
			$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			try {
				$argumentWS = [
								"as_wspass" => $config["WS_STRC_DB"],
								"as_account_no" => $deptaccount_no,
								"as_itemtype_code" => "WTX",
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
					$is_separate = $func->getConstant("separate_limit_amount_trans_online");
					$getLimitAllDay = $conoracle->prepare("SELECT total_limit FROM atmucftranslimit WHERE tran_desc = 'MCOOP' and tran_status = 1");
					$getLimitAllDay->execute();
					$rowLimitAllDay = $getLimitAllDay->fetch(PDO::FETCH_ASSOC);
					if($is_separate){
						$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
															WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
															and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP') and SUBSTR(deptitemtype_code,0,1) = 'W'");
					}else{
						$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
															WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
															and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP')");
					}
					$getSumAllDay->execute();
					$rowSumAllDay = $getSumAllDay->fetch(PDO::FETCH_ASSOC);
					if(($rowSumAllDay["SUM_AMT"] + $SumAmt_transfer) > $rowLimitAllDay["TOTAL_LIMIT"]){
						$arrayResult["RESPONSE_CODE"] = 'WS0043';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
					if($amt_transfer > 0){
						$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
						$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
						$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
						$arrayResult['CAUTION'] = $arrayCaution;
					}
					$arrayResult['FEE_AMT'] = $amt_transfer;
					$arrayResult['FEE_AMT_FORMAT'] = number_format($amt_transfer,2);
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