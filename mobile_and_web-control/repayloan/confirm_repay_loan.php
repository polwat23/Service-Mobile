<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],'WTX');
		if($arrInitDep["RESULT"]){
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
			if($arrRightDep["RESULT"]){
				if(isset($arrInitDep["PENALTY_AMT"]) && $arrInitDep["PENALTY_AMT"] > 0){
					$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
					$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
					$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
					$arrayResult['CAUTION'] = $arrayCaution;
					$arrayResult['FEE_AMT'] = $arrInitDep["PENALTY_AMT"];
					$arrayResult['FEE_AMT_FORMAT'] = number_format($arrInitDep["PENALTY_AMT"],2);
				}
				try {
					$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
					try {
						$arrayGroup = array();
						$arrayGroup["coop_id"] = $config["COOP_ID"];
						$arrayGroup["loancontract_no"] = $dataComing["loancontract_no"];
						$arrayGroup["member_no"] = $member_no;
						$arrayGroup["operate_date"] = $dateOperC;
						$arrayGroup["slip_date"] = $dateOperC;
						$arrayGroup["entry_id"] = $dataComing["channel"] == 'mobile_app' ? "MCOOP" : "ICOOP";
						$argumentWS = [
							"as_wspass" => $config["WS_STRC_DB"],
							"astr_lninitloans" => $arrayGroup
						];
						$resultWS = $clientWS->__call("of_initslippayin_mobile", array($argumentWS));
						if($resultWS->of_initslippayin_mobileResult == '1'){
							$resultService = $resultWS->astr_lninitloans;
							$arrayResult['RESULT'] = TRUE;
							if($resultService->fee_amt > 0){
								$arrayResult["RESPONSE_CODE"] = 'WS0105';
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								//$arrayResult['FEE_AMT'] = $resultService->fee_amt;
							}
							if($resultService->fine_amt > 0){
								$arrayResult['PENALTY_AMT'] = $resultService->fine_amt;
							}
							require_once('../../include/exit_footer.php');
						}else{
							$arrayStruc = [
								':member_no' => $payload["member_no"],
								':id_userlogin' => $payload["id_userlogin"],
								':operate_date' => $dateOper,
								':deptaccount_no' => $deptaccount_no,
								':amt_transfer' => $dataComing["amt_transfer"],
								':status_flag' => '0',
								':destination' => $dataComing["loancontract_no"],
								':response_code' => "WS0066",
								':response_message' => json_encode($resultWS)
							];
							$log->writeLog('repayloan',$arrayStruc);
							$arrayResult["RESPONSE_CODE"] = 'WS0066';
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}catch(SoapFault $e){
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $dataComing["amt_transfer"],
							':status_flag' => '0',
							':destination' => $dataComing["contract_no"],
							':response_code' => "WS0066",
							':response_message' => json_encode($e)
						];
						$log->writeLog('repayloan',$arrayStruc);
						$arrayResult["RESPONSE_CODE"] = 'WS0066';
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}catch(Throwable $e){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS0066",
						":error_desc" => "ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage(),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." ไมสามารถต่อไปยัง Service ชำระหนี้ได้ "."\n"."Error => ".$e->getMessage()."\n"."DATA => ".json_encode($dataComing);
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult["RESPONSE_CODE"] = 'WS0066';
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