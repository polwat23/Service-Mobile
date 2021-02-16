<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','source_deptaccount_no','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		if($dataComing["source_deptaccount_no"] == $dataComing["deptaccount_no"]){
			$arrayResult['RESPONSE_CODE'] = "WS0045";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrayDept = array();
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no 
												FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
												WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gct.allow_deposit_inside = '1'");
		$fetchAccAllowTrans->execute([':deptaccount_no' => $dataComing["deptaccount_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			$arrarDataAcc = array();
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["MemberID"] = substr($member_no,-6);
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS9999",
					":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponseAPI = json_decode($arrResponseAPI);
			if($arrResponseAPI->responseCode == "200"){
				foreach($arrResponseAPI->accountDetail as $accData){
					if ($accData->coopAccountNo == $dataComing["deptaccount_no"]){
						if($accData->accountStatus == "0" && $accData->creditFlag == "0"){
							$checkAllowToTransaction = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = :member_no");
							$checkAllowToTransaction->execute([':member_no' => $payload["member_no"]]);
							if($checkAllowToTransaction->rowCount() > 0){
								$arrarDataAcc["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
								$arrarDataAcc["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
								$arrarDataAcc["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($accData->coopAccountNo,$func->getConstant('hidden_dep'));
								$arrarDataAcc["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
								$arrarDataAcc["DEPT_TYPE"] = $accData->accountDesc;
								$arrayResult['ACCOUNT_DATA'] = $arrarDataAcc;
								$arrayResult['RESULT'] = TRUE;
								require_once('../../include/exit_footer.php');
							}else{
								$arrayResult['RESPONSE_CODE'] = "WS0026";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
								
							}
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0054";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0025";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS9001";
				if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
					$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0026";
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